<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One immutable row per redaction of a law.
     *
     * A version is identified by its date of last change: the ДВ publication
     * that produced the text. That date is already in the data and already
     * moves — ДВ бр. 69 shifted publ_date to 2026-07-31 for 23 laws at once —
     * but the fetch overwrote the law in place, so the superseded text survived
     * only in the git history of data/. Nothing could answer what чл. 80а said
     * on a given date, which is the question the project is named after.
     *
     * publ_date is the identity and start_date the applicability: they differ
     * (a law published on 31.07 may take effect on 01.10), so both are kept.
     */
    public function up(): void
    {
        Schema::create('law_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained()->cascadeOnDelete();

            // Дата на последна промяна — the version's identity.
            $table->date('changed_at');

            // The ДВ issue that carried the change, i.e. how the version is
            // named in citation: "ДВ бр. 69 от 2026 г.".
            $table->unsignedInteger('dv')->nullable();
            $table->unsignedInteger('publ_year')->nullable();

            // Applicability, not identity. valid_to stays open on the current
            // version and closes the moment a later one supersedes it.
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();

            $table->json('content_structure')->nullable();
            $table->json('content_text')->nullable();

            // Guards the case the dates cannot see: a payload that changed
            // without publ_date moving.
            $table->string('source_hash', 64)->nullable();

            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['law_id', 'changed_at']);
            $table->index(['law_id', 'valid_from']);
        });

        Schema::table('laws', function (Blueprint $table) {
            $table->foreignId('current_version_id')
                ->nullable()
                ->after('id')
                ->constrained('law_versions')
                ->nullOnDelete();
        });

        Schema::table('law_nodes', function (Blueprint $table) {
            $table->foreignId('law_version_id')
                ->nullable()
                ->after('law_id')
                ->constrained('law_versions')
                ->cascadeOnDelete();
        });

        $this->archiveExistingContent();

        Schema::table('laws', function (Blueprint $table) {
            $table->dropColumn(['content_structure', 'content_text']);
        });
    }

    /**
     * The daily export restores a cached database and then migrates, so
     * dropping the payload columns without moving them first would wipe the
     * content of every law and force a full re-fetch of the corpus from
     * APIS.BG. Each law's current content becomes its first version instead.
     */
    private function archiveExistingContent(): void
    {
        DB::table('laws')
            ->whereNotNull('content_structure')
            ->orderBy('id')
            ->select(['id', 'publ_date', 'start_date', 'end_date', 'dv', 'publ_year', 'content_structure', 'content_text', 'content_fetched_at', 'processed_at'])
            ->chunkById(25, function ($laws): void {
                foreach ($laws as $law) {
                    $changedAt = $law->publ_date !== null
                        ? substr((string) $law->publ_date, 0, 10)
                        : substr((string) ($law->content_fetched_at ?? now()), 0, 10);

                    $versionId = DB::table('law_versions')->insertGetId([
                        'law_id' => $law->id,
                        'changed_at' => $changedAt,
                        'dv' => $law->dv,
                        'publ_year' => $law->publ_year,
                        'valid_from' => $law->start_date === null ? null : substr((string) $law->start_date, 0, 10),
                        'valid_to' => $law->end_date === null ? null : substr((string) $law->end_date, 0, 10),
                        'content_structure' => $law->content_structure,
                        'content_text' => $law->content_text,
                        'source_hash' => hash('sha256', (string) $law->content_structure.'|'.(string) $law->content_text),
                        'fetched_at' => $law->content_fetched_at,
                        'processed_at' => $law->processed_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('laws')->where('id', $law->id)->update(['current_version_id' => $versionId]);
                    DB::table('law_nodes')->where('law_id', $law->id)->update(['law_version_id' => $versionId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('laws', function (Blueprint $table) {
            $table->json('content_structure')->nullable()->after('seria');
            $table->json('content_text')->nullable()->after('content_structure');
        });

        // Chunked rather than an UPDATE ... JOIN, whose syntax differs per
        // driver and is not what the query builder emits for PostgreSQL.
        DB::table('law_versions')
            ->join('laws', 'laws.current_version_id', '=', 'law_versions.id')
            ->orderBy('law_versions.id')
            ->select(['law_versions.id', 'laws.id as law_id', 'law_versions.content_structure', 'law_versions.content_text'])
            ->chunkById(25, function ($versions): void {
                foreach ($versions as $version) {
                    DB::table('laws')->where('id', $version->law_id)->update([
                        'content_structure' => $version->content_structure,
                        'content_text' => $version->content_text,
                    ]);
                }
            }, 'law_versions.id', 'id');

        Schema::table('law_nodes', function (Blueprint $table) {
            $table->dropForeign(['law_version_id']);
            $table->dropColumn('law_version_id');
        });

        Schema::table('laws', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
            $table->dropColumn('current_version_id');
        });

        Schema::dropIfExists('law_versions');
    }
};

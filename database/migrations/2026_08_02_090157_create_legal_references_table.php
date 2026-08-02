<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per citation a provision makes.
     *
     * References were previously reduced to a has_in_links boolean: the HTML to
     * Markdown pass strips anchors, so what a provision pointed at was thrown
     * away and only the fact that it pointed somewhere survived. In law a
     * reference is a dependency — a sanction, a definition, an exception, a
     * delegation — and no amount of semantic similarity recovers it.
     *
     * Rows hang off the source node and cascade with it: nodes are rebuilt
     * delete-then-insert on every reprocess, so references can be stale-free by
     * construction rather than by bookkeeping.
     */
    public function up(): void
    {
        Schema::create('legal_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_node_id')->constrained('law_nodes')->cascadeOnDelete();

            // Null until the target is resolved: an external act is named
            // before it is matched, and an internal path can point at a
            // provision this law does not (yet) contain.
            $table->foreignId('target_node_id')->nullable()->constrained('law_nodes')->nullOnDelete();
            $table->foreignId('target_law_id')->nullable()->constrained('laws')->nullOnDelete();
            $table->string('target_path')->nullable();
            $table->string('target_act_name')->nullable();

            $table->text('citation_text');
            $table->string('relation_type')->default('refers_to');
            $table->string('resolution_status');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index('source_node_id', 'legal_references_source_idx');
            $table->index('target_node_id', 'legal_references_target_idx');
            $table->index(['law_id', 'target_path']);
            $table->index(['law_id', 'resolution_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_references');
    }
};

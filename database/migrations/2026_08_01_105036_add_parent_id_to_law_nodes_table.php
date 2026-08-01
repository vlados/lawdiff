<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structural hierarchy used to be inferred by stripping the last "/segment"
     * off a node's path. That only holds while every ancestor is also a path
     * prefix, which stops being true once глава/раздел are preserved: article
     * numbering runs law-wide, so чл. 80а stays ЧЛ80А even inside a chapter.
     * parent_id carries the real tree independently of the citation path.
     */
    public function up(): void
    {
        Schema::table('law_nodes', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('law_id')
                ->constrained('law_nodes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('law_nodes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};

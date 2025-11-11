<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laws', function (Blueprint $table) {
            $table->json('content_structure')->nullable()->after('seria');
            $table->json('content_text')->nullable()->after('content_structure');
            $table->timestamp('content_fetched_at')->nullable()->after('content_text');

            $table->index('content_fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laws', function (Blueprint $table) {
            $table->dropIndex(['content_fetched_at']);
            $table->dropColumn(['content_structure', 'content_text', 'content_fetched_at']);
        });
    }
};

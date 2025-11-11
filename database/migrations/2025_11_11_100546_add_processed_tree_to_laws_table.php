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
            $table->json('processed_tree')->nullable()->after('content_fetched_at');
            $table->timestamp('processed_at')->nullable()->after('processed_tree');

            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laws', function (Blueprint $table) {
            $table->dropIndex(['processed_at']);
            $table->dropColumn(['processed_tree', 'processed_at']);
        });
    }
};

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
        Schema::create('law_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained()->cascadeOnDelete();
            $table->string('path')->index();
            $table->integer('p_id')->nullable();
            $table->string('caption')->nullable();
            $table->text('text_markdown')->nullable();
            $table->string('node_type')->nullable();
            $table->integer('type')->nullable();
            $table->integer('field_type')->nullable();
            $table->boolean('has_in_links')->default(false);
            $table->integer('sort_order');
            $table->integer('level')->default(0);
            $table->boolean('is_orphaned')->default(false);
            $table->timestamps();

            $table->index(['law_id', 'path']);
            $table->index(['law_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('law_nodes');
    }
};

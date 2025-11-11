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
        Schema::create('laws', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unique_id')->unique();
            $table->unsignedInteger('db_index')->default(0);
            $table->text('caption');
            $table->unsignedTinyInteger('func');
            $table->unsignedTinyInteger('type');
            $table->string('base', 50);
            $table->boolean('is_actual')->default(true);
            $table->dateTime('publ_date');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->dateTime('act_date');
            $table->unsignedInteger('publ_year');
            $table->boolean('is_connected')->default(false);
            $table->boolean('has_content')->default(false);
            $table->string('code', 50);
            $table->unsignedInteger('dv');
            $table->unsignedBigInteger('original_id')->nullable();
            $table->string('version', 50)->nullable();
            $table->string('celex', 100)->nullable();
            $table->string('doc_lead', 100)->nullable();
            $table->string('seria', 50)->nullable();
            $table->timestamps();

            $table->index('unique_id');
            $table->index('is_actual');
            $table->index('publ_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laws');
    }
};

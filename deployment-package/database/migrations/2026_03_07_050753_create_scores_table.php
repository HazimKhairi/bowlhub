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
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->string('participant_id');
            $table->integer('g1')->default(0);
            $table->integer('g2')->default(0);
            $table->integer('g3')->default(0);
            $table->integer('g4')->default(0);
            $table->integer('g5')->default(0);
            $table->integer('total')->default(0);
            $table->decimal('average', 8, 1)->default(0);
            $table->timestamps();

            $table->foreign('participant_id')
                ->references('id')
                ->on('participants')
                ->onDelete('cascade');

            $table->index('participant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};

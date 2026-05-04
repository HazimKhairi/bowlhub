<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_score_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->string('nickname', 100);
            $table->integer('g1')->default(0);
            $table->integer('g2')->default(0);
            $table->integer('g3')->default(0);
            $table->integer('g4')->default(0);
            $table->integer('g5')->default(0);
            $table->integer('total')->default(0);
            $table->enum('reason', ['no_match', 'multiple_matches', 'invalid_data'])->default('no_match');
            $table->json('match_candidates')->nullable();
            $table->enum('status', ['pending', 'resolved', 'discarded'])->default('pending');
            $table->string('resolved_participant_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('row_number')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->foreign('resolved_participant_id')
                ->references('id')
                ->on('participants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_score_imports');
    }
};

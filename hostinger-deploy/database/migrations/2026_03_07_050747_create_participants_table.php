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
        Schema::create('participants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('ic')->unique();
            $table->string('phone');
            $table->string('team');
            $table->enum('gender', ['lelaki', 'wanita']);
            $table->enum('event_type', ['individu', 'beregu', 'trio', 'berkumpulan']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};

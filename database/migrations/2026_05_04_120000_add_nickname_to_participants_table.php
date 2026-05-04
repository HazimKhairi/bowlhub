<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('nickname', 100)->nullable()->after('name');
            $table->unique('nickname');
            $table->index('nickname', 'participants_nickname_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex('participants_nickname_lookup');
            $table->dropUnique(['nickname']);
            $table->dropColumn('nickname');
        });
    }
};

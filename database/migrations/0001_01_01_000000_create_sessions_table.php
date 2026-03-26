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
        Schema::create('sessions', function (Blueprint $user_sessions) {
            $user_sessions->string('id')->primary();
            $user_sessions->foreignId('user_id')->nullable()->index();
            $user_sessions->string('ip_address', 45)->nullable();
            $user_sessions->text('user_agent')->nullable();
            $user_sessions->longText('payload');
            $user_sessions->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};

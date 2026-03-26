<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('current_state');
            $table->string('next_state');
            $table->string('required_role');
            $table->json('condition_expression')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['current_state', 'is_active']);
            $table->unique(['current_state', 'next_state', 'required_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_rules');
    }
};

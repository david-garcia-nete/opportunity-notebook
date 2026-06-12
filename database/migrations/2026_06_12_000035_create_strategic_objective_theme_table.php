<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_objective_theme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategic_objective_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['strategic_objective_id', 'theme_id'], 'objective_theme_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_objective_theme');
    }
};

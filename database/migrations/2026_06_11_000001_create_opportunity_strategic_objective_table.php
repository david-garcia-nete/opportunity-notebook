<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_strategic_objective', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_objective_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['opportunity_id', 'strategic_objective_id'], 'opportunity_objective_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_strategic_objective');
    }
};

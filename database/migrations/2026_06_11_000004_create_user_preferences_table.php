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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('income_weight')->default(5);
            $table->unsignedTinyInteger('probability_weight')->default(5);
            $table->unsignedTinyInteger('time_to_revenue_weight')->default(5);
            $table->unsignedTinyInteger('strategic_alignment_weight')->default(5);
            $table->unsignedTinyInteger('personal_interest_weight')->default(5);
            $table->unsignedTinyInteger('skill_growth_weight')->default(5);
            $table->unsignedTinyInteger('family_fit_weight')->default(5);
            $table->unsignedTinyInteger('risk_weight')->default(5);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};

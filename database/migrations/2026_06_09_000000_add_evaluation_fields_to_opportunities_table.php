<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->unsignedTinyInteger('income_potential')->nullable()->after('score');
            $table->unsignedTinyInteger('probability_of_success')->nullable()->after('income_potential');
            $table->unsignedTinyInteger('time_to_revenue')->nullable()->after('probability_of_success');
            $table->unsignedTinyInteger('strategic_alignment')->nullable()->after('time_to_revenue');
            $table->unsignedTinyInteger('personal_interest')->nullable()->after('strategic_alignment');
            $table->unsignedTinyInteger('skill_growth')->nullable()->after('personal_interest');
            $table->unsignedTinyInteger('family_fit')->nullable()->after('skill_growth');
            $table->unsignedTinyInteger('risk_level')->nullable()->after('family_fit');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'income_potential',
                'probability_of_success',
                'time_to_revenue',
                'strategic_alignment',
                'personal_interest',
                'skill_growth',
                'family_fit',
                'risk_level',
            ]);
        });
    }
};

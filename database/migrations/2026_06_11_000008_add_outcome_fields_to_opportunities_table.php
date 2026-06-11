<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('outcome')->nullable()->after('focus_reason');
            $table->date('outcome_date')->nullable()->after('outcome');
            $table->text('outcome_notes')->nullable()->after('outcome_date');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'outcome',
                'outcome_date',
                'outcome_notes',
            ]);
        });
    }
};

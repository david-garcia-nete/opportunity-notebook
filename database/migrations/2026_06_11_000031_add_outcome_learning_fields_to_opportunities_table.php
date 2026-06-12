<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('outcome_reason')->nullable()->after('outcome_date');
            $table->text('lesson_learned')->nullable()->after('outcome_notes');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'outcome_reason',
                'lesson_learned',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->boolean('is_focus')->default(false)->after('risk_level');
            $table->timestamp('focused_at')->nullable()->after('is_focus');
            $table->text('focus_reason')->nullable()->after('focused_at');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'is_focus',
                'focused_at',
                'focus_reason',
            ]);
        });
    }
};

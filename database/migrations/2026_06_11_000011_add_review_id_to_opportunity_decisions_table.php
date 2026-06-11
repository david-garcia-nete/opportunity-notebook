<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunity_decisions', function (Blueprint $table) {
            $table->foreignId('review_id')
                ->nullable()
                ->after('opportunity_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_decisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('review_id');
        });
    }
};

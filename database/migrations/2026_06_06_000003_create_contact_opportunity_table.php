<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_opportunity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('relationship_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'opportunity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_opportunity');
    }
};

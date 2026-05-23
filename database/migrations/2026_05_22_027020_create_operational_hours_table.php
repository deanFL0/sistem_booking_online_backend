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
        Schema::create('operational_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week')->unsigned(); // 0 (Sunday) to 6 (Saturday)
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_closed')->default(false); // Indicates if the business is closed on this day
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_hours');
    }
};

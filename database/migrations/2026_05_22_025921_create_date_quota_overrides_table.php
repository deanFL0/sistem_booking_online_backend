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
        Schema::create('date_quota_overrides', function (Blueprint $table) {
            $table->id();
            $table->date('override_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('custom_quota')->default(1);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('date_quota_overrides');
    }
};

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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // for logged in user
            $table->foreignId('resource_id')->nullable()->constrained('resources')->onDelete('set null'); // assigned resource (barber, chair, etc.)
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->date('booking_date');
            $table->time('booking_time');
            $table->integer('duration_minutes')->default(0); // Actual/confirmed duration in minutes
            $table->time('booking_end_time')->nullable(); // Calculated end time
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->decimal('total_price', 9, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

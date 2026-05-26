<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateBookingRequest;
use App\Http\Requests\RescheduleBookingRequest;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return BookingResource::collection(Booking::paginate(25));
    }

    // Display list of bookings for the authenticated user
    public function myBookings()
    {
        $user = auth()->user();
        return BookingResource::collection(Booking::where('user_id', $user->id)->paginate(25));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request, BookingAvailabilityService $bookingAvailabilityService)
    {
        $data = $request->validated();

        $service = Service::findOrFail($data['service_id']);
        // calculate total price
        if ($service->pricing_type === 'one_time') {
            $totalPrice = $service->price;
        } 
        if ($service->pricing_type === 'hourly') {
            // For hourly pricing, we will calculate the total price based on the duration the service
            $totalPrice = $service->price * $service->duration / 60;
        }

        // getservice duration
        $duration = $service->duration;

        // calculate end datetime based on start datetime and service duration
        $endDatetime = Carbon::parse($data['start_datetime'])->addMinutes($duration);

        // Check booking availability
        $allocatedResources = $bookingAvailabilityService->isBookingAvailable(
            $request->service_id,
            Carbon::parse($request->start_datetime),
            Carbon::parse($endDatetime->format('Y-m-d H:i:s'))
        );
        if (!$allocatedResources) {
            return response()->json(['message' => 'The selected time slot is not available for booking.'], 400);
        }

        // Create booking        
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'service_id' => $data['service_id'],
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'start_datetime' => $data['start_datetime'],
            'end_datetime' => $endDatetime->format('Y-m-d H:i:s'),
            'duration_minutes' => $duration, 
            'total_price' => $totalPrice,
            'status' => 'confirmed',
        ]);

        if ($allocatedResources) {
            $booking->resource()->attach($allocatedResources->pluck('id'));
        }

        return new BookingResource($booking);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);
        return new BookingResource($booking);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminUpdateBookingRequest $request, Booking $booking)
    {
        $booking->update($request->validated());
        return new BookingResource($booking);
    }

    public function reschedule(RescheduleBookingRequest $request, Booking $booking)
    {
        // Dissallow rescheduling if the booking is already cancelled or completed
        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return response()->json(['message' => 'Booking cannot be rescheduled'], 400);
        }

        // Dissallow rescheduling within set number of hours before the booking start time
        $minRescheduleTime = (int) setting('minimum_reschedule', 24);
        if ($booking->start_datetime->diffInHours(now()) < $minRescheduleTime) {
            return response()->json(['message' => 'Booking cannot be rescheduled within ' . $minRescheduleTime . ' hours of the book time'], 400);
        }

        $booking->update($request->validated());
        return new BookingResource($booking);
    }

    public function cancel(CancelBookingRequest $request, Booking $booking)
    {
        // Dissallow cancellation if the booking is already cancelled or completed
        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return response()->json(['message' => 'Booking cannot be cancelled'], 400);
        }

        // Dissallow cancellation within set number of hours before the booking start time
        $minCancellationTime = (int) setting('minimum_cancel', 24);
        if ($booking->start_datetime->diffInHours(now()) < $minCancellationTime) {
            return response()->json(['message' => 'Booking cannot be cancelled within ' . $minCancellationTime . ' hours of the book time'], 400);
        }

        $booking->update($request->validated());
        return new BookingResource($booking);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully'], 200);
    }
}

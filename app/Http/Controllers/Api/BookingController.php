<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateBookingRequest;
use App\Http\Requests\RescheduleBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingCreatedMail;
use App\Mail\BookingRescheduledMail;
use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

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
    public function store(StoreBookingRequest $request, BookingService $bookingService)
    {
        $data = $request->validated();

        // check if customer exceed booking limit
        $bookingService->ensureBookingLimit($data);

        // getservice duration
        $service = Service::findOrFail($data['service_id']);
        $duration = $service->duration;

        // calculate total price
        $totalPrice = $bookingService->calculateTotalPrice($data['service_id']);

        // calculate end datetime based on start datetime and service duration
        $endDatetime = $bookingService->calculateEndDatetime(
            $data['service_id'], Carbon::parse($data['start_datetime'])
        );

        // Check booking availability
        $allocatedResources = $bookingService->getBookingResources(
            $request->service_id,
            Carbon::parse($request->start_datetime),
        );

        // Create booking
        $booking = Booking::create([
            'user_id' => auth('sanctum')->id(),
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

        // Attach allocated resources to the booking
        if ($allocatedResources) {
            $booking->resources()->attach($allocatedResources->pluck('id'));
        }

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingCreatedMail($booking));

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

    public function guestShow($token)
    {
        $booking = Booking::where('manage_token', $token)->firstOrFail();

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

    public function reschedule(RescheduleBookingRequest $request, Booking $booking, BookingService $bookingService)
    {
        $request->validated();

        $bookingService->validateBookingReschedule(
            $booking,
            Carbon::parse($request->start_datetime),
        );

        $booking->update($request->validated());

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingRescheduledMail($booking));

        return new BookingResource($booking);
    }

    public function guestReschedule(RescheduleBookingRequest $request, $token, BookingService $bookingService)
    {
        $request->validated();

        $booking = Booking::where('manage_token', $token)->firstOrFail();

        $bookingService->validateBookingReschedule(
            $booking,
            Carbon::parse($request->start_datetime),
        );

        $booking->update($request->validated());

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingRescheduledMail($booking));

        return new BookingResource($booking);
    }

    public function cancel(Booking $booking, BookingService $bookingService)
    {
        $bookingService->validateBookingCancellation($booking);

        $booking->update(['status' => 'cancelled']);

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingCancelledMail($booking));

        return new BookingResource($booking);
    }

    public function guestCancel($token, BookingService $bookingService)
    {
        $booking = Booking::where('manage_token', $token)->firstOrFail();

        $bookingService->validateBookingCancellation($booking);

        $booking->update(['status' => 'cancelled']);

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingCancelledMail($booking));

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

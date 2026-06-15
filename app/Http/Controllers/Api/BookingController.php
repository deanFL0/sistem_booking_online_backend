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
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = QueryBuilder::for(Booking::class)
            ->defaultSort('id')
            ->allowedSorts(
                'id', 'customer_name', 'customer_email',
                'start_datetime', 'end_datetime', 'duration_minutes',
                'total_price', 'status'
            )
            ->allowedIncludes('service', 'resources', 'user')
            ->allowedFilters([
                'booking_code', 'customer_name', 'customer_email', 'customer_phone',
                'start_datetime', 'end_datetime', 'duration_minutes',
                'total_price', 'status', 'service.name',
                AllowedFilter::scope('min_start_datetime'),
                AllowedFilter::scope('max_start_datetime'),
                AllowedFilter::scope('max_end_datetime'),
                AllowedFilter::scope('min_duration'),
                AllowedFilter::scope('max_duration'),
                AllowedFilter::scope('min_price'),
                AllowedFilter::scope('max_price'),
            ])
            ->paginate(25)
            ->appends(request()->query());

        return BookingResource::collection($bookings);
    }

    // Display list of bookings for the authenticated user
    public function myBookings()
    {
        $user = auth()->user();

        $bookings = QueryBuilder::for(Booking::class)
            ->where('user_id', $user->id)
            ->defaultSort('id')
            ->allowedSorts(
                'id', 'customer_name', 'customer_email',
                'start_datetime', 'end_datetime', 'duration_minutes',
                'total_price', 'status'
            )
            ->allowedIncludes('service')
            ->allowedFilters([
                'customer_name', 'customer_email', 'customer_phone',
                'start_datetime', 'end_datetime', 'duration_minutes',
                'total_price', 'status', 'service.name',
                AllowedFilter::scope('min_time'),
                AllowedFilter::scope('max_time'),
                AllowedFilter::scope('min_duration'),
                AllowedFilter::scope('max_duration'),
                AllowedFilter::scope('min_price'),
                AllowedFilter::scope('max_price'),
            ])
            ->paginate(request('per_page', 10))
            ->appends(request()->query());
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

        // Get user ID if authenticated, otherwise null for guest bookings
        $userId = auth('sanctum')->check() ? auth('sanctum')->id() : null;

        // Create booking
        $booking = Booking::create([
            'user_id' => $userId,
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

        $booking->load('service');

        return new BookingResource($booking);
    }

    public function guestShow($token)
    {
        $booking = Booking::where('manage_token', $token)->firstOrFail();
        $booking->load('service');

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

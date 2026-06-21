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
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Mail;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BookingController extends Controller
{
    use AuthorizesRequests;

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
        $user = auth('sanctum')->user();

        $bookings = QueryBuilder::for(Booking::class)
            ->where('user_id', $user->id)
            ->defaultSort('id')
            ->allowedSorts(
                'id', 'customer_name', 'customer_email',
                'start_datetime', 'end_datetime', 'duration_minutes',
                'total_price', 'status', 'created_at'
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

        return BookingResource::collection($bookings);
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
    public function store(StoreBookingRequest $request, BookingService $bookingService, AvailabilityService $availabilityService)
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
            'user_id' => $data['user_id'] ?? null,
            'service_id' => $data['service_id'],
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
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

        $availabilityService->invalidateServiceAvailability($booking->service_id);

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
    public function update(AdminUpdateBookingRequest $request, Booking $booking, BookingService $bookingService, AvailabilityService $availabilityService)
    {
        $data = $request->validated();

        // If admin only updates customer info (no service or start change), perform a simple update
        $requiresFullValidation = array_key_exists('service_id', $data) || array_key_exists('start_datetime', $data);

        if (! $requiresFullValidation) {
            $booking->update($data);

            return new BookingResource($booking);
        }

        // Determine new service and start values (fall back to existing booking values)
        $serviceId = $data['service_id'] ?? $booking->service_id;
        $start = isset($data['start_datetime'])
            ? Carbon::parse($data['start_datetime'])
            : Carbon::parse($booking->start_datetime);

        // Validate reschedule rules (status/min-reschedule time)
        $bookingService->validateBookingReschedule($booking, $start);

        // Validate service availability, resources, and compute derived fields
        $allocatedResources = $bookingService->getBookingResources($serviceId, $start);

        $endDatetime = $bookingService->calculateEndDatetime($serviceId, $start);

        // Get service duration and total price
        $service = Service::findOrFail($serviceId);
        $duration = $service->duration;
        $totalPrice = $bookingService->calculateTotalPrice($serviceId);

        // Merge computed fields into update payload
        $updateData = array_merge($data, [
            'service_id' => $serviceId,
            'start_datetime' => $start->format('Y-m-d H:i:s'),
            'end_datetime' => $endDatetime->format('Y-m-d H:i:s'),
            'duration_minutes' => $duration,
            'total_price' => $totalPrice,
        ]);

        $booking->update($updateData);

        // Sync allocated resources
        if ($allocatedResources && $allocatedResources->isNotEmpty()) {
            $booking->resources()->sync($allocatedResources->pluck('id')->toArray());
        } else {
            $booking->resources()->detach();
        }

        // Invalidate availability for both previous and updated services, if changed.
        $oldServiceId = $booking->wasChanged('service_id') ? $booking->getOriginal('service_id') : $booking->service_id;
        $availabilityService->invalidateServiceAvailability($booking->service_id);

        if (isset($oldServiceId) && $oldServiceId !== $booking->service_id) {
            $availabilityService->invalidateServiceAvailability($oldServiceId);
        }

        // Notify customer about the change
        Mail::to($booking->customer_email)->send(new BookingRescheduledMail($booking));

        return new BookingResource($booking);
    }

    public function reschedule(RescheduleBookingRequest $request, Booking $booking, BookingService $bookingService, AvailabilityService $availabilityService)
    {
        $request->validated();

        $bookingService->validateBookingReschedule(
            $booking,
            Carbon::parse($request->start_datetime),
        );

        $booking->update($request->validated());

        $availabilityService->invalidateServiceAvailability($booking->service_id);

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingRescheduledMail($booking));

        return new BookingResource($booking);
    }

    public function guestReschedule(RescheduleBookingRequest $request, $token, BookingService $bookingService, AvailabilityService $availabilityService)
    {
        $request->validated();

        $booking = Booking::where('manage_token', $token)->firstOrFail();

        $bookingService->validateBookingReschedule(
            $booking,
            Carbon::parse($request->start_datetime),
        );

        $booking->update($request->validated());

        $availabilityService->invalidateServiceAvailability($booking->service_id);

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingRescheduledMail($booking));

        return new BookingResource($booking);
    }

    public function cancel(Booking $booking, BookingService $bookingService, AvailabilityService $availabilityService)
    {
        $bookingService->validateBookingCancellation($booking);

        $booking->update(['status' => 'cancelled']);

        $availabilityService->invalidateServiceAvailability($booking->service_id);

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingCancelledMail($booking));

        return new BookingResource($booking);
    }

    public function guestCancel($token, BookingService $bookingService, AvailabilityService $availabilityService)
    {
        $booking = Booking::where('manage_token', $token)->firstOrFail();

        $bookingService->validateBookingCancellation($booking);

        $booking->update(['status' => 'cancelled']);

        $availabilityService->invalidateServiceAvailability($booking->service_id);

        // Send booking confirmation email to the customer
        Mail::to($booking->customer_email)->send(new BookingCancelledMail($booking));

        return new BookingResource($booking);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking, AvailabilityService $availabilityService)
    {
        $serviceId = $booking->service_id;
        $booking->delete();

        $availabilityService->invalidateServiceAvailability($serviceId);

        return response()->json(['message' => 'Booking deleted successfully'], 200);
    }
}

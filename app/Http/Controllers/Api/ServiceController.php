<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableDatesRequest;
use App\Http\Requests\AvailableTimeSlotsRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = QueryBuilder::for(Service::class)
            ->allowedIncludes('resourceTypes')
            ->defaultSort('id')
            ->allowedSorts('id', 'name', 'price', 'pricing_type', 'duration', 'is_active')
            ->allowedFilters([
                'name', 'price', 'pricing_type', 'duration',
                AllowedFilter::exact('is_active'),
                AllowedFilter::scope('max_duration'),
                AllowedFilter::scope('min_duration'),
                AllowedFilter::scope('min_price'),
                AllowedFilter::scope('max_price'),
            ])
            ->paginate(request('per_page', 10))
            ->appends(request()->query());

        return ServiceResource::collection($services);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }

        $service = Service::create($data);

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        $service->load('resourceTypes');

        return new ServiceResource($service);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service, AvailabilityService $availabilityService)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($service->image_path) {
                Storage::disk('public')->delete($service->image_path);
            }
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }

        $service->update($data);
        $availabilityService->invalidateServiceAvailability($service->id);

        return new ServiceResource($service);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service, AvailabilityService $availabilityService)
    {
        // Delete image if it exists
        if ($service->image_path) {
            Storage::disk('public')->delete($service->image_path);
        }

        $availabilityService->invalidateServiceAvailability($service->id);
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully'], 200);
    }

    /**
     * Get all services.
     * This is used for populating dropdowns or selection lists in the frontend.
     */
    public function options()
    {
        return Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function availableDates(
        AvailableDatesRequest $request,
        Service $service,
        AvailabilityService $availabilityService
    ) {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));

        $availableDates = $availabilityService->getAvailableDates(
            $service->id,
            $startDate
        );

        return response()->json([
            'service_id' => $service->id,
            'start_date' => $startDate,
            'available_dates' => $availableDates,
        ]);
    }

    public function availableTimeSlots(
        AvailableTimeSlotsRequest $request,
        Service $service,
        AvailabilityService $availabilityService
    ) {
        $availableSlots = $availabilityService->getAvailableTimeSlots(
            $service->id,
            $request->input('date')
        );

        return response()->json([
            'service_id' => $service->id,
            'date' => $request->input('date'),
            'available_time_slots' => $availableSlots,
        ]);
    }
}

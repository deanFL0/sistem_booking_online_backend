<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceAvailabilityOverrideRequest;
use App\Http\Requests\UpdateResourceAvailabilityOverrideRequest;
use App\Http\Resources\ResourceAvailabilityOverrideResource;
use App\Models\Resource;
use App\Models\ResourceAvailabilityOverride;
use App\Services\AvailabilityService;
use App\Services\ResourceService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ResourceAvailabilityOverrideController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $override = QueryBuilder::for(ResourceAvailabilityOverride::class)
            ->defaultSort('id')
            ->allowedSorts('id', 'start_datetime', 'end_datetime', 'status')
            ->allowedFilters([
                'start_datetime', 'end_datetime', 'status',
                AllowedFilter::scope('min_start_datetime'),
                AllowedFilter::scope('max_start_datetime'),
                AllowedFilter::scope('min_end_datetime'),
                AllowedFilter::scope('max_end_datetime'),
            ])
            ->paginate(request('per_page', 10))
            ->appends(request()->query());

        return ResourceAvailabilityOverrideResource::collection($override);
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
    public function store(StoreResourceAvailabilityOverrideRequest $request, Resource $resource, ResourceService $resourceService, AvailabilityService $availabilityService)
    {
        $availabilityOverride = $resource->availabilityOverrides()->create($request->validated());

        $resourceService->processOverride($availabilityOverride);
        $availabilityService->invalidateServicesByResource($resource);

        return (new ResourceAvailabilityOverrideResource(
            $availabilityOverride
        ))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource, ResourceAvailabilityOverride $availabilityOverride)
    {
        return new ResourceAvailabilityOverrideResource($availabilityOverride);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResourceAvailabilityOverride $availabilityOverride)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceAvailabilityOverrideRequest $request, Resource $resource, ResourceAvailabilityOverride $availabilityOverride, ResourceService $resourceService, AvailabilityService $availabilityService)
    {
        $availabilityOverride->update($request->validated());

        $resourceService->processOverride($availabilityOverride);
        $availabilityService->invalidateServicesByResource($availabilityOverride->resource);

        return new ResourceAvailabilityOverrideResource($availabilityOverride);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource, ResourceAvailabilityOverride $availabilityOverride, AvailabilityService $availabilityService)
    {
        $availabilityOverride->delete();

        if ($resource) {
            $availabilityService->invalidateServicesByResource($resource);
        }

        return response()->json(['message' => 'Resource availability override deleted successfully'], 200);
    }
}

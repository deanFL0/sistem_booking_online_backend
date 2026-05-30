<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceAvailabilityOverrideRequest;
use App\Http\Requests\UpdateResourceAvailabilityOverrideRequest;
use App\Http\Resources\ResourceAvailabilityOverrideResource;
use App\Models\ResourceAvailabilityOverride;
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
        $override = QueryBuilder::for(ResourceAvailabilityOverrideResource::class)
        ->defaultSort('id')
        ->allowedSorts('id', 'resource.name', 'start_time', 'end_time', 'status')
        ->allowedFilters([
            'resource.name',
            AllowedFilter::scope('before_time'),
            AllowedFilter::scope('after_time'),
            AllowedFilter::scope('on_day'),
        ])
        ->paginate(25)
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
    public function store(StoreResourceAvailabilityOverrideRequest $request, ResourceService $resourceService)
    {
        $resourceAvailabilityOverride = ResourceAvailabilityOverride::create($request->validated());

        $resourceService->processOverride($resourceAvailabilityOverride);

        return (new ResourceAvailabilityOverrideResource(
            $resourceAvailabilityOverride
        ))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ResourceAvailabilityOverride $resourceAvailabilityOverride)
    {
        return new ResourceAvailabilityOverrideResource($resourceAvailabilityOverride);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResourceAvailabilityOverride $resourceAvailabilityOverride)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceAvailabilityOverrideRequest $request, ResourceAvailabilityOverride $resourceAvailabilityOverride, ResourceService $resourceService)
    {
        $resourceAvailabilityOverride->update($request->validated());

        $resourceService->processOverride($resourceAvailabilityOverride);

        return new ResourceAvailabilityOverrideResource($resourceAvailabilityOverride);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResourceAvailabilityOverride $resourceAvailabilityOverride)
    {
        $resourceAvailabilityOverride->delete();

        return response()->json(['message' => 'Resource availability override deleted successfully'], 200);
    }
}

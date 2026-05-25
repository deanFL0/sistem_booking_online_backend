<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceAvailabilityOverrideRequest;
use App\Http\Requests\UpdateResourceAvailabilityOverrideRequest;
use App\Http\Resources\ResourceAvailabilityOverrideResource;
use App\Models\ResourceAvailabilityOverride;
use Illuminate\Http\Request;

class ResourceAvailabilityOverrideController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ResourceAvailabilityOverrideResource::collection(ResourceAvailabilityOverride::paginate(25));
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
    public function store(StoreResourceAvailabilityOverrideRequest $request)
    {
        $resourceAvailabilityOverride = ResourceAvailabilityOverride::create($request->validated());

        return (new ResourceAvailabilityOverrideResource($resourceAvailabilityOverride))->response()->setStatusCode(201);
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
    public function update(UpdateResourceAvailabilityOverrideRequest $request, ResourceAvailabilityOverride $resourceAvailabilityOverride)
    {
        $resourceAvailabilityOverride->update($request->validated());

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

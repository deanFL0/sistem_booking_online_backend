<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceResourceTypeRequest;
use App\Http\Requests\UpdateServiceResourceTypeRequest;
use App\Http\Resources\ResourceTypeResource;
use App\Models\ResourceType;
use App\Models\Service;
use App\QueryBuilder\ServiceResourceTypeQuery;
use App\Services\AvailabilityService;

class ServiceResourceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Service $service)
    {
        $resourceType = ServiceResourceTypeQuery::build($service)
            ->paginate(request('per_page', 10))
            ->appends(request()->query());

        return ResourceTypeResource::collection($resourceType);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceResourceTypeRequest $request, Service $service, AvailabilityService $availabilityService)
    {
        $data = $request->validated();

        // attach resource type to service with quantity
        $service->resourceTypes()
            ->syncWithoutDetaching([$data['resource_type_id'] => ['quantity' => $data['quantity']]]);

        $availabilityService->invalidateServiceAvailability($service->id);

        return response()->json(['message' => 'Resource type assigned successfully']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceResourceTypeRequest $request, Service $service, ResourceType $resourceType, AvailabilityService $availabilityService)
    {
        $data = $request->validated();

        // update the quantity of the resource type for the service
        $service->resourceTypes()->updateExistingPivot($data['resource_type_id'], ['quantity' => $data['quantity']]);

        $availabilityService->invalidateServiceAvailability($service->id);

        return response()->json(['message' => 'Quantity updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service, ResourceType $resourceType, AvailabilityService $availabilityService)
    {
        // detach resource type from service
        $service->resourceTypes()->detach($resourceType->id);

        $availabilityService->invalidateServiceAvailability($service->id);

        return response()->json(['message' => 'Resource type unassigned successfully']);
    }
}

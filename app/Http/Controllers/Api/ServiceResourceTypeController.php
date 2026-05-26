<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceResourceTypeRequest;
use App\Http\Requests\UpdateServiceResourceTypeRequest;
use App\Http\Resources\ResourceTypeResource;
use App\Models\Service;
use App\Models\ResourceType;
use Illuminate\Http\Request;

class ServiceResourceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Service $service)
    {
        return ResourceTypeResource::collection($service->resourceTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceResourceTypeRequest $request, Service $service)
    {
        $data = $request->validated();

        // attach resource type to service with quantity
        $service->resourceTypes()
            ->syncWithoutDetaching([$data['resource_type_id'] => ['quantity' => $data['quantity']]]);

        return response()->json(['message' => 'Resource type assigned successfully']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceResourceTypeRequest $request, Service $service, ResourceType $resourceType)
    {
        $data = $request->validated();

        // update the quantity of the resource type for the service
        $service->resourceTypes()->updateExistingPivot($data['resource_type_id'], ['quantity' => $data['quantity']]);

        return response()->json(['message' => 'Quantity updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service, ResourceType $resourceType)
    {
        // detach resource type from service
        $service->resourceTypes()->detach($resourceType->id);

        return response()->json(['message' => 'Resource type unassigned successfully']);
    }
}

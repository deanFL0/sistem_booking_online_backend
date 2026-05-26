<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceTypeRequest;
use App\Http\Requests\UpdateResourceTypeRequest;
use App\Http\Resources\ResourceTypeResource;
use App\Models\ResourceType;
use Illuminate\Http\Request;

class ResourceTypeController extends Controller
{
    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        return ResourceTypeResource::collection(ResourceType::all());
    }

    /**
    * Store a newly created resource in storage.
    */
    public function store(StoreResourceTypeRequest $request)
    {
        $resourceType = ResourceType::create($request->validated());
        return new ResourceTypeResource($resourceType);
    }

    /**
    * Display the specified resource.
    */
    public function show(ResourceType $resourceType)
    {
        return new ResourceTypeResource($resourceType);
    }

    /**
    * Update the specified resource in storage.
    */
    public function update(UpdateResourceTypeRequest $request, ResourceType $resourceType)
    {
        $resourceType->update($request->validated());
        return new ResourceTypeResource($resourceType);
    }

    /**
    * Remove the specified resource from storage.
    */
    public function destroy(ResourceType $resourceType)
    {
        $resourceType->delete();
        return response()->json(null, 204);
    }
}

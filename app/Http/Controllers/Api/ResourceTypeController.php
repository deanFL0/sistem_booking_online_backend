<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceTypeRequest;
use App\Http\Requests\UpdateResourceTypeRequest;
use App\Http\Resources\ResourceTypeResource;
use App\Models\ResourceType;
use Spatie\QueryBuilder\QueryBuilder;

class ResourceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resourceType = QueryBuilder::for(ResourceType::class)
            ->defaultSort('id')
            ->allowedSorts('id', 'name')
            ->allowedFilters('name')
            ->paginate(25)
            ->appends(request()->query());

        return ResourceTypeResource::collection($resourceType);
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

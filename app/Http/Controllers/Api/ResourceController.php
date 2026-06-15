<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class ResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resource = QueryBuilder::for(Resource::class)
            ->allowedIncludes([
                AllowedInclude::relationship('resource_type', 'resourceType'),
                AllowedInclude::relationship('operational_hours', 'operationalHours'),
                AllowedInclude::relationship('availability_overrides', 'availabilityOverrides'),
            ])
            ->defaultSort('id')
            ->allowedSorts('id', 'name', 'is_active')
            ->allowedFilters(
                'name',
                AllowedFilter::partial('resource_type_name', 'resourceType.name'),
                AllowedFilter::exact('resource_type_id', 'resource_type_id'),
                AllowedFilter::exact('is_active')
            )
            ->paginate(request('per_page', 10))
            ->appends(request()->query());

        return ResourceResource::collection($resource);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request)
    {
        $resource = Resource::create($request->validated());

        return (new ResourceResource($resource))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource)
    {
        $resource->load('resourceType', 'operationalHours', 'availabilityOverrides');

        return new ResourceResource($resource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $resource->update($request->validated());

        return new ResourceResource($resource);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        $resource->delete();

        return response()->json(['message' => 'Resource deleted successfully'], 200);
    }
}

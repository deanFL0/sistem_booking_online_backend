<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;

class ResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ResourceResource::collection(Resource::paginate(25));
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

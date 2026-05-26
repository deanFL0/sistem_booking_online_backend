<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceResourceController extends Controller
{
        /**
        * Display a listing of the resource.
        */
        public function index(Service $service)
        {
            return ResourceResource::collection($service->resources);
        }
    
        /**
        * Store a newly created resource in storage.
        */
        public function store(Request $request, Service $service)
        {
            $request->validate([
                'resource_id' => 'required|exists:resources,id',
            ]);

            $service->resources()->syncWithoutDetaching($request->resource_id);

            return response()->json(['message' => 'Resource attached to service successfully']);
        }
    
        /**
        * Display the specified resource.
        */
        public function show(Service $service, Resource $resource)
        {
            return new ResourceResource($resource);
        }
    
        /**
        * Update the specified resource in storage.
        */
        public function update(Request $request, string $id)
        {
            //
        }
    
        /**
        * Remove the specified resource from storage.
        */
        public function destroy(Service $service, Resource $resource)
        {
            $service->resources()->detach($resource->id);

            return response()->json(['message' => 'Resource removed from service successfully']);
        }
}

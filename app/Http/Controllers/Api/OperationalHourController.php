<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOperationalHourRequest;
use App\Http\Requests\UpdateOperationalHourRequest;
use App\Http\Resources\OperationalHourResource;
use App\Models\OperationalHour;
use App\Models\Resource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OperationalHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Resource $resource)
    {
        $operationalHour = QueryBuilder::for(OperationalHour::class)
        ->where('resource_id', $resource->id)
        ->defaultSort('id')
        ->allowedSorts('id', 'day_of_week', 'open_time', 'close_time', 'is_closed')
        ->allowedFilters([
            'day_of_week', 'is_closed',
            AllowedFilter::scope('before_time'),
            AllowedFilter::scope('after_time'),
        ])
        ->paginate(25)
        ->appends(request()->query());
        
        return new OperationalHourResource($operationalHour);
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
    public function store(StoreOperationalHourRequest $request, Resource $resource)
    {
        $operationalHour = $resource->operationalHours()->create($request->validated());

        return (new OperationalHourResource($operationalHour))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource, OperationalHour $operationalHour)
    {
        return new OperationalHourResource($operationalHour);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OperationalHour $operationalHour, Resource $resource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOperationalHourRequest $request, Resource $resource, OperationalHour $operationalHour)
    {
        $operationalHour->update($request->validated());

        return new OperationalHourResource($operationalHour);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource, OperationalHour $operationalHour)
    {
        $operationalHour->delete();

        return response()->json('Operational hour deleted successfully', 200);
    }
}

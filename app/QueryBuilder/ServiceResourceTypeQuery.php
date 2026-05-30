<?php

namespace App\QueryBuilder;

use App\Models\Service;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class ServiceResourceTypeQuery
{
    public static function build(Service $service)
    {
        return QueryBuilder::for(
            $service->resourceTypes()
        )
            ->allowedFilters([
                'name',

                AllowedFilter::callback(
                    'quantity',
                    fn ($query, $value) =>
                        $query->wherePivot('quantity', $value)
                ),

                AllowedFilter::callback(
                    'min_quantity',
                    fn ($query, $value) =>
                        $query->wherePivot('quantity', '>=', $value)
                ),

                AllowedFilter::callback(
                    'max_quantity',
                    fn ($query, $value) =>
                        $query->wherePivot('quantity', '<=', $value)
                ),
            ])

            ->allowedSorts([
                'name',

                AllowedSort::callback(
                    'quantity',
                    fn ($query, $descending) =>
                        $query->orderBy(
                            'resource_type_service.quantity',
                            $descending ? 'desc' : 'asc'
                        )
                ),
            ]);
    }
}
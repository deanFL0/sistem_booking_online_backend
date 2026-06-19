<?php

namespace App\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class ServiceTotalPriceSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';

        $query->orderByRaw("
            CASE
                WHEN pricing_type = 'hourly'
                THEN price * CEIL(duration / 60.0)
                ELSE price
            END {$direction}
        ");
    }
}

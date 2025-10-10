<?php

namespace App\Support;

use Closure;
use InvalidArgumentException;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Paginate the given query.
     *
     * @param int|Closure|null $perPage
     * @param string $pageName
     * @param int|null $page
     * @param Closure|int|null $total
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws InvalidArgumentException
     */
    public function paginate($perPage, $pageName = 'page', $page, $total)
    {
        $results = $total
            ? $this->forPage($page, $perPage)->values()
            : new Collection();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Create a new length-aware paginator instance.
     *
     * @param BaseCollection $items
     * @param int $total
     * @param int $perPage
     * @param int $currentPage
     * @param array $options
     * @return LengthAwarePaginator
     */
    protected function paginator($items, $total, $perPage, $currentPage, $options)
    {
        return new LengthAwarePaginator($items, $total, $perPage, $currentPage, $options);
    }
}

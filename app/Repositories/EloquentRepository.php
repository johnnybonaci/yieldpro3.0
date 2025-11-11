<?php

namespace App\Repositories;

use App\Models\Leads\Pub;
use App\Models\Leads\Sub;
use App\Models\Leads\Offer;
use Illuminate\Support\Collection;
use App\Models\Leads\TrafficSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class EloquentRepository
{
    /**
     * find by id.
     */
    public function find(int $id, Model $model): Collection
    {
        return new Collection($model::find($id));
    }

    /**
     * All record from param models.
     */
    public function getAll(Model $model): EloquentCollection
    {
        return $model::all();
    }

    /**
     * All record active, scopeActive must be defined in model.
     */
    public function getActiveAll(Model $model): EloquentCollection
    {
        return $model::active()->get();
    }

    /**
     * Record by where condition.
     */
    public function getByWhere(string $model, array $where): Collection
    {
        return new Collection($model::where($where)->get());
    }

    /**
     *  Set fields to providers.
     */
    public function setFields(Collection &$data): array
    {
        $pub_id = $this->find($data['pub_id'], new Pub());
        $sub_id = $this->find($data['sub_id'], new Sub());
        $response['offers'] = $this->find($pub_id->get('offer_id'), new Offer());
        $traffic_source = $this->find($pub_id->get('pub_list_id'), new TrafficSource());
        $traffic_source_id = $traffic_source->count() ? $pub_id->get('pub_list_id') : 100;

        $data['pub_id'] = $pub_id->get('pub_list_id');
        $data['sub_id'] = $sub_id->get('sub_id');
        $data['traffic_source_id'] = $traffic_source_id;

        return $response;
    }

    /**
     * Update record by id.
     */
    public function updateById(int $id, Model $model, array $data): void
    {
        $model::find($id)->update($data);
    }

    /**
     * Process job from providars & phone rooms.
     */
    public function process(Collection $data, array $job): void
    {
        dispatch(new $job['job']($data, $job['model']))->onQueue($job['queue']);
    }
}

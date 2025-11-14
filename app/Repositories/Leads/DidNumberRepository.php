<?php

namespace App\Repositories\Leads;

use App\Traits\SavesWithResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Leads\DidNumber;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\SettingsRepositoryInterface;
use App\Interfaces\Leads\ImportRepositoryInterface;

class DidNumberRepository implements ImportRepositoryInterface, SettingsRepositoryInterface
{
    use SavesWithResponse;
    /**
     * Summary of create.
     */
    public function create(Collection $data, int $quantity): int
    {
        $data->chunk($quantity)->each(fn ($chunk) => DidNumber::upsert($chunk->toArray(), ['id'], ['traffic_source_id', 'offer_id', 'description']));

        return $data->count();
    }

    /**
     * Summary of Resource.
     * @param mixed $provider
     */
    public function resource(array $data, $provider): array
    {
        $id = Str::remove('+1', $data['number']);
        $td = $this->getTrafficSourceId($data['traffic_source_id'], $provider);
        $of = $this->getOfferId($data['offer_id'], $provider);

        if ($td && $of) {
            return
                [
                    $id => [
                        'id' => $id,
                        'traffic_source_id' => $td,
                        'offer_id' => $of,
                        'description' => $data['description'],
                        'created_at' => now(),
                    ],
                ];
        } else {
            return [];
        }
    }

    /**
     * Summary of getOfferId.
     */
    public function getOfferId(int $offer_id, int $provider): ?int
    {
        return (new OfferRepository())->getById($offer_id, $provider);
    }

    /**
     * Summary of getTrafficSourceId.
     */
    public function getTrafficSourceId(int $offer_id, int $provider): int
    {
        return (new TrafficSourceRepository())->getById($offer_id, $provider);
    }

    public function getDidNumbers(): Builder
    {
        return DidNumber::query();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getQuery(): Builder
    {
        return $this->getDidNumbers();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function save(Request $request, Model $model): array
    {
        return $this->saveDidNumbers($request, $model);
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getDefaultSortField(): string
    {
        return 'id';
    }

    public function saveDidNumbers(Request $request, DidNumber $did_number): array
    {
        return $this->saveWithResponse($did_number, 'DID Number', function ($model) use ($request) {
            $pub_id = $request->get('pub_id');
            $model->fill([
                'description' => $request->get('description'),
                'campaign_name' => $request->get('campaign_name') ?? '',
                'sub_id' => $request->get('sub_id'),
                'pub_id' => ($pub_id === 'undefined' || empty($pub_id)) ? null : $pub_id,
                'traffic_source_id' => $request->get('traffic_source_id'),
                'offer_id' => $request->get('offer_id'),
            ]);
        });
    }
}

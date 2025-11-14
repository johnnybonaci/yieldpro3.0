<?php

namespace App\Repositories\Leads;

use Exception;
use Carbon\Carbon;
use App\Traits\SavesWithResponse;
use App\Models\Leads\Buyer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Interfaces\Leads\ImportRepositoryInterface;

class BuyerRepository extends AbstractSettingsRepository implements ImportRepositoryInterface
{
    use SavesWithResponse;
    /**
     * Summary of create.
     */
    public function create(Collection $data, int $quantity): int
    {
        $updateFields = env('APP_SYNC_REV') ? ['name', 'revenue'] : ['name'];
        $data->chunk($quantity)->each(fn ($chunk) => Buyer::upsert($chunk->toArray(), ['id'], $updateFields));

        return $data->count();
    }

    /**
     * Summary of Resource.
     * @param mixed $provider
     */
    public function resource(array $data, $provider): array
    {
        return
            [
                $data['user_buyer_id'] => [
                    'id' => $data['user_buyer_id'] . $provider,
                    'name' => $data['name'],
                    'buyer_provider_id' => $data['id'],
                    'provider_id' => $provider,
                    'revenue' => $data['bid_price'] ?? 0,
                    'created_at' => now(),
                ],
            ];
    }

    public function getBuyers(): Builder
    {
        return $this->getSettingsQuery();
    }

    protected function getSettingsQuery(): Builder
    {
        return Buyer::query()->where('created_at', '>=', Carbon::now()->subMonths(10));
    }

    protected function saveSettings(Request $request, Model $buyer): array
    {
        return $this->saveWithResponse($buyer, 'Buyer', function ($model) use ($request) {
            $model->name = $request->get('name');
            $model->buyer_provider_id = $request->get('buyer_provider_id');
            $model->provider_id = env('TRACKDRIVE_PROVIDER_ID');
            $model->user_id = $request->get('user_id');
            $model->revenue = $request->get('revenue');
        });
    }

    public function saveSelection(Request $request): array
    {
        $icon = 'success';
        $message = 'Transcription settings updated successfully';

        try {
            $buyerIds = $request->input('buyerIds', []);

            if (empty($buyerIds)) {
                return [
                    'icon' => 'error',
                    'message' => 'No buyers selected',
                    'response' => false,
                ];
            }

            Buyer::query()->update(['enable_transcriptions' => false]);

            $updated = Buyer::whereIn('id', $buyerIds)
                ->update(['enable_transcriptions' => true]);

            if ($updated === 0) {
                $icon = 'error';
                $message = 'No buyers were updated. Please verify the selected buyers exist.';
            } else {
                $message = "Transcription enabled for {$updated} buyer(s)";
            }

            $response = [
                'icon' => $icon,
                'message' => $message,
                'response' => $updated > 0,
                'updated_count' => $updated,
            ];
        } catch (Exception $e) {
            $response = [
                'icon' => 'error',
                'message' => 'Error updating transcription settings: ' . $e->getMessage(),
                'response' => false,
            ];
        }

        return $response;
    }
}

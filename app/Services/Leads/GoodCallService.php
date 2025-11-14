<?php

namespace App\Services\Leads;

use Illuminate\Support\Str;

class GoodCallService extends AbstractCallService
{
    public const BASE_URL = 'https://dialer.vp.genxcontactcenter.com/vicidial';

    public const PHONE_ROOM = 1;

    protected function getEndpoint(): string
    {
        return 'non_agent_api.php';
    }

    protected function getBaseUrl(): string
    {
        return self::BASE_URL;
    }

    protected function getPhoneRoomId(): int
    {
        return self::PHONE_ROOM;
    }

    protected function parseSuccessResponse(string $responseBody, array $data): array
    {
        if (Str::contains($responseBody, 'SUCCESS:')) {
            $explodId = Str::of(trim($responseBody, "\n"))
                ->explode($data['phone_number'] . '|1001')[1];

            return [
                'status' => 'success',
                'lead_id' => Str::betweenFirst($explodId, '|', '|'),
                'message' => json_encode([]),
            ];
        }

        return [
            'status' => 'error',
            'message' => $responseBody,
        ];
    }
}

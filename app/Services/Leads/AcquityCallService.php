<?php

namespace App\Services\Leads;

class AcquityCallService extends AbstractCallService
{
    public const BASE_URL = 'https://api.convoso.com/v1/leads';

    public const PHONE_ROOM = 3;

    protected function getEndpoint(): string
    {
        return 'insert';
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
        $message = json_decode($responseBody, true);

        if (isset($message['success']) && $message['success'] === true) {
            return [
                'status' => 'success',
                'lead_id' => $message['data']['lead_id'],
                'message' => json_encode([]),
            ];
        }

        return [
            'status' => 'error',
            'message' => json_encode($responseBody),
        ];
    }
}

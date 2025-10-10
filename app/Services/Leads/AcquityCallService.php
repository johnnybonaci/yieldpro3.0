<?php

namespace App\Services\Leads;

use App\Repositories\LogRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Interfaces\Leads\PostingServiceInterface;

class AcquityCallService implements PostingServiceInterface
{
    public const BASE_URL = 'https://api.convoso.com/v1/leads';

    public const PHONE_ROOM = 3;

    public function __construct(
        private LogRepository $log_repository,
    ) {
    }

    /**
     * Posting Lead to PhoneRoom TruAlliant.
     */
    public function submit(array $data): bool
    {
        $log = [
            'phone' => $data['phone_number'],
            'request' => self::BASE_URL . '/insert?' . http_build_query($data, '&'),
            'status' => 'error',
            'lead_id' => '',
        ];

        try {
            $response = Http::baseUrl(self::BASE_URL)->get('insert', $data)->throw();

            $message = json_decode($response->body(), true);
            if (isset($message['success']) && $message['success'] === true) {
                $log['status'] = 'success';
                $log['lead_id'] = $message['data']['lead_id'];
                $message = json_encode([]);
            } else {
                $message = json_encode($response->body());
            }

            $this->log_repository->logginPhoneRoom($message, $log, self::PHONE_ROOM);

            return true;
        } catch (RequestException $e) {
            $this->log_repository->logginPhoneRoom($e->response->body(), $log, self::PHONE_ROOM);

            return false;
        }
    }
}

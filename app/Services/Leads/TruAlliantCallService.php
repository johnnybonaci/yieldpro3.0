<?php

namespace App\Services\Leads;

use Illuminate\Support\Str;
use App\Repositories\LogRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Interfaces\Leads\PostingServiceInterface;

class TruAlliantCallService implements PostingServiceInterface
{
    public const BASE_URL = 'https://massnexus.phdialer.com/vicidial';

    public const PHONE_ROOM = 2;

    public function __construct(
        private LogRepository $log_repository,
    ) {
    }

    /**
     * Posting Lead to PhoneRoom TruAlliant.
     */
    public function submit(array $data): bool
    {
        $log['phone'] = $data['phone_number'];
        $list_id = $data['list_id'];

        $log['request'] = self::BASE_URL . '/non_agent_api.php?' . http_build_query($data, '&');

        try {
            $response = Http::baseUrl(self::BASE_URL)->get('non_agent_api.php', $data)->throw();
            $message = $response->body();
            $log['status'] = 'error';
            $log['lead_id'] = '';
            $log['request'] = self::BASE_URL . '/non_agent_api.php?' . http_build_query($data, '&');

            if (Str::contains($message, 'SUCCESS:')) {
                $explodId = Str::of(trim($message, "\n"))->explode($data['phone_number'] . '|' . $list_id)[1];
                $log['status'] = 'success';
                $log['lead_id'] = Str::betweenFirst($explodId, '|', '|');
                $message = json_encode([]);
            }
            $this->log_repository->logginPhoneRoom($message, $log, self::PHONE_ROOM);
            $response = true;
        } catch (RequestException $e) {
            $log['status'] = 'error';
            $this->log_repository->logginPhoneRoom($e->response->body(), $log, self::PHONE_ROOM);
            $response = false;
        }

        return $response;
    }
}

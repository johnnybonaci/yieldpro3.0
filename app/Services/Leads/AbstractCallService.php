<?php

namespace App\Services\Leads;

use App\Repositories\LogRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Interfaces\Leads\PostingServiceInterface;

/**
 * Abstract Call Service - Eliminates Duplication
 *
 * Consolidates submit() pattern from 4 call services:
 * - ConvosoCallService
 * - AcquityCallService
 * - TruAlliantCallService
 * - GoodCallService
 *
 * Reduces ~85 lines of duplicated submit code using Template Method pattern.
 *
 * Each service only needs to implement:
 * - BASE_URL constant
 * - PHONE_ROOM constant
 * - getEndpoint() method
 * - parseSuccessResponse() method
 */
abstract class AbstractCallService implements PostingServiceInterface
{
    public function __construct(
        protected LogRepository $log_repository,
    ) {
    }

    /**
     * Get the API endpoint path.
     * Example: 'insert' or 'non_agent_api.php'
     */
    abstract protected function getEndpoint(): string;

    /**
     * Parse successful response and extract lead_id.
     * Returns ['status' => 'success', 'lead_id' => '123', 'message' => '...']
     * or ['status' => 'error', 'message' => '...']
     */
    abstract protected function parseSuccessResponse(string $responseBody, array $data): array;

    /**
     * Get the phone room ID for logging.
     */
    abstract protected function getPhoneRoomId(): int;

    /**
     * Get the base URL for the service.
     */
    abstract protected function getBaseUrl(): string;

    /**
     * Template method for submitting lead to phone room.
     * This method eliminates duplicate code across all call services.
     */
    public function submit(array $data): bool
    {
        $log = $this->buildInitialLog($data);

        try {
            $response = Http::baseUrl($this->getBaseUrl())
                ->get($this->getEndpoint(), $data)
                ->throw();

            $parsed = $this->parseSuccessResponse($response->body(), $data);

            $log['status'] = $parsed['status'];
            $log['lead_id'] = $parsed['lead_id'] ?? '';
            $message = $parsed['message'] ?? json_encode([]);

            $this->log_repository->logginPhoneRoom($message, $log, $this->getPhoneRoomId());

            return true;
        } catch (RequestException $e) {
            $this->log_repository->logginPhoneRoom(
                $e->response->body(),
                $log,
                $this->getPhoneRoomId()
            );

            return false;
        }
    }

    /**
     * Build initial log data structure.
     */
    protected function buildInitialLog(array $data): array
    {
        return [
            'phone' => $data['phone_number'],
            'request' => $this->getBaseUrl() . '/' . $this->getEndpoint() . '?' . http_build_query($data, '&'),
            'status' => 'error',
            'lead_id' => '',
        ];
    }
}

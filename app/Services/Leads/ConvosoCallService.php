<?php

namespace App\Services\Leads;

use Illuminate\Support\Facades\Http;

class ConvosoCallService extends AbstractCallService
{
    public const BASE_URL = 'https://api.convoso.com/v1/leads';

    public const PHONE_ROOM = 4;

    public function verify(array $data)
    {
        $reg = ['phone' => $data['phone_number']];
        $reg['product'] = 'ACA';
        $response = Http::get('https://callconnectionleadsuppression.azurewebsites.net/api/leads', $reg)->throw();
        if ($response->body() == 'notfound') {
            $this->submit($data);
        }
    }

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

    public function sendSupresionList(array $data)
    {
        $list = [
            'phone_number' => (string) $data['phone_number'],
            'lead_source' => 'MassNexusACA',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'state' => $data['state'],
            'email' => $data['email'],
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://prod-76.westus.logic.azure.com:443/workflows/fbafa3d4ca21415fa321f0a2bb9ea210/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=RKelzWZDsSMJE4E2mOM6NA2w4ZHIndN0aezgBfGx6Rs', $list);

        $res = json_decode($response->body(), true);
        $res['send'] = 'Supresion List';

        $log['status'] = 'success';
        $log['lead_id'] = $res['status'] == 'success' ? $res['id'] : random_int(1000, 9999);
        $log['phone'] = $data['phone_number'];

        $message = json_encode($res);

        $this->log_repository->logginPhoneRoom($message, $log, self::PHONE_ROOM);
    }
}

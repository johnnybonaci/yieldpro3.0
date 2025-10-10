<?php

namespace App\Http\Controllers\Api\Leads;

use App\Models\Leads\BotLeads;
use Illuminate\Http\JsonResponse;
use App\Services\Leads\LeadService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\Leads\LeadBotRequest;
use App\Repositories\Leads\LeadApiRepository;

class LeadBotController extends Controller
{
    public function __construct(
        protected LeadApiRepository $lead_api_repository,
        protected LeadService $lead_service,
    ) {
    }

    public function store(LeadBotRequest $request): JsonResponse
    {
        $code = Config::get('area_code.code');
        $insert = $this->lead_api_repository->resource($request->all());
        $phone = substr($insert['phone'], 0, 3);
        if ($this->lead_service->isInvalidName($insert['first_name']) || $this->lead_service->isInvalidName($insert['last_name'])) {
            $insert['rejected'] = 1;

            return response()->json([
                'status' => 'error',
                'message' => 'the first or last name cannot be null or empty.',
            ], 422);
        }
        if (!in_array($phone, $code)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The area code is not valid.',
            ], 422);
        }

        if (!$request->get('universal_leadid')) {
            return response()->json([
                'status' => 'error',
                'message' => 'The given data lead is not certificated.',
                'errors' => [
                    'universal_leadid' => [
                        'The universal_leadid field is not displayed.',
                    ],
                ],
            ], 422);
        }
        $response = $this->lead_api_repository->createBot($insert);
        if ($response['status']) {
            if ($response['create']) {
                $this->lead_api_repository->create($insert);
                $this->lead_service->dispatch($insert);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data has been saved successfully',
                    'code' => $response['code'],
                ], 200);
            } elseif (!$response['create']) {
                $this->lead_api_repository->jobBotDuplicate($insert);
                $this->lead_api_repository->create($insert);
                $this->lead_service->dispatch($insert);

                return response()->json([
                    'status' => 'success',
                    'message' => 'The duplicated leads have been processed.',
                ], 200);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'The data could not be processed.',
        ], 422);
    }

    public function update(LeadBotRequest $request): JsonResponse
    {
        BotLeads::where('phone', $request->get('phone'))->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Data has been saved successfully',
        ], 200);
    }
}

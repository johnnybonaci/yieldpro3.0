<?php

namespace App\Http\Controllers\Api\Tests;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use App\Enums\TranscriptStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\Leads\TranscriptionJob;
use Illuminate\Support\Facades\Auth;

class TestsController extends Controller
{
    public function process(Request $request): mixed
    {
        $request->validate([
            'phone' => 'required|string',
            'date' => 'required|date',
            'reset' => 'sometimes|string|in:transcript,analysis',
        ]);

        $phone = $request->input('phone');
        $date = $request->date('date');

        $reset = $request->input('reset');

        /** @var \App\Models\Leads\Lead $lead */
        $lead = \App\Models\Leads\Lead::query()->where('phone', $phone)->get()->firstOrFail();

        /** @var \App\Models\Leads\Convertion $convertion */
        $convertion = $lead->convertions()->where('created_at', $date)->firstOrFail();

        /** @var \App\Models\Leads\Recording $recording */
        $recording = $convertion->record()->firstOrFail();

        $resetData = match ($reset) {
            'transcript' => ['transcript' => null, 'multiple' => null],
            'analysis' => ['multiple' => null, 'qa_status' => null],
            default => ['record' => null, 'transcript' => null, 'multiple' => null, 'qa_status' => null],
        };

        if ($resetData) {
            $recording->update(array_merge($resetData, [
                'status' => 2,
                'billable' => 0,
                'insurance' => 2,
            ]));
        }

        TranscriptionJob::dispatch([
            'id' => $recording->id,
            'date_start' => $date->format('Y-m-d'),
            'date_end' => $date->format('Y-m-d'),
        ], Auth::loginUsingId(23, $remember = true))->onQueue('transcript');

        $recording->update(['status' => TranscriptStatusEnum::TRANSCRIBING->value]);

        return response()->json([
            'message' => "Call processing started: {$request->input('call_id')}",
        ]);
    }

    public function search(Request $request): mixed
    {
        $request->validate([
            'phone' => 'required|string',
            'date' => 'required|date',
        ]);

        $phone = $request->input('phone');
        $date = $request->date('date');

        /** @var \App\Models\Leads\Lead $lead */
        $lead = \App\Models\Leads\Lead::query()->where('phone', $phone)->get()->firstOrFail();

        /** @var \App\Models\Leads\Convertion $convertion */
        $convertion = $lead->convertions()->where('created_at', $date)->firstOrFail();

        /** @var \App\Models\Leads\Recording $recording */
        $recording = $convertion->record()->firstOrFail();

        return $recording;
    }

    public function transcript(Request $request): string
    {
        $request->validate([
            'phone' => 'required|string',
            'date' => 'required|date',
        ]);

        $phone = $request->input('phone');
        $date = $request->date('date');

        /** @var \App\Models\Leads\Lead $lead */
        $lead = \App\Models\Leads\Lead::query()->where('phone', $phone)->get()->firstOrFail();

        /** @var \App\Models\Leads\Convertion $convertion */
        $convertion = $lead->convertions()->where('created_at', $date)->firstOrFail();

        /** @var \App\Models\Leads\Recording $recording */
        $recording = $convertion->record()->firstOrFail();

        return $recording->transcript;
    }

    public function ai(Request $request): string
    {
        $request->validate([
            'system' => 'required|string',
            'user' => 'required|string',
        ]);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $request->input('system')],
                ['role' => 'user', 'content' => $request->input('user')],
            ],
            'temperature' => 0,
        ]);

        return $response['choices'][0]['message']['content'];
    }

    public function ask(Request $request): string
    {
        $request->validate([
            'phone' => 'required|string',
            'date' => 'required|date',
            'question' => 'required|string',
        ]);

        $phone = $request->input('phone');
        $date = $request->date('date');
        $text = $request->input('question');

        /** @var \App\Models\Leads\Lead $lead */
        $lead = \App\Models\Leads\Lead::query()->where('phone', $phone)->get()->firstOrFail();

        /** @var \App\Models\Leads\Convertion $convertion */
        $convertion = $lead->convertions()->where('created_at', $date)->firstOrFail();

        /** @var \App\Models\Leads\Recording $recording */
        $recording = $convertion->record()->firstOrFail();

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $recording->getAttribute('transcript')],
                ['role' => 'user', 'content' => $text],
            ],
            'temperature' => 0,
        ]);

        return $response['choices'][0]['message']['content'];
    }
}

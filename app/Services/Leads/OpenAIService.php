<?php

namespace App\Services\Leads;

use App\Models\Leads\Recording;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Pipeline;
use App\Services\Leads\ProcessCalls\ProcessAudio;
use App\Services\Leads\ProcessCalls\TranscribeAudio;
use App\Services\Leads\ProcessCalls\ProcessTranscript;

readonly class OpenAIService
{
    public function analyze(Recording $recording): Recording
    {
        return Pipeline::send($recording)
            ->through([
                ProcessAudio::class,
                TranscribeAudio::class,
                match (intval($recording->convertion->offer_id)) {
                    20012 => ProcessTranscript::class,
                    default => ProcessTranscript::class,
                },
            ])
            ->then(fn (Recording $recording) => $recording);
    }

    public function ask(string $question, int $recordingId): string
    {
        $recording = Recording::query()->findOrFail($recordingId);

        $transcript = $recording->transcript;

        $prompt = view('prompts.ask', [
            'question' => $question,
            'transcript' => $transcript,
        ])->render();

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0,
        ]);

        return $response['choices'][0]['message']['content'];
    }
}

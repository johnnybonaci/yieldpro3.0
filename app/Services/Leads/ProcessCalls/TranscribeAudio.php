<?php

namespace App\Services\Leads\ProcessCalls;

use Closure;
use Exception;
use App\Models\Leads\Recording;
use OpenAI\Laravel\Facades\OpenAI;
use App\Enums\TranscriptStatusEnum;
use App\Repositories\Leads\OpenAIRepository;

readonly class TranscribeAudio
{
    public function __construct(
        private OpenAIRepository $openaiRepository,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(Recording $recording, Closure $next): mixed
    {
        logger()->channel('ai')->info('Transcribing audio', ['recording' => $recording->id]);

        if ($recording->getAttribute('transcript')) {
            return $next($recording);
        }

        $recordPath = $this->getRecordPath($recording);

        $result = OpenAI::audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($recordPath, 'r'),
            'response_format' => 'verbose_json',
            'prompt' => config('openai.whisper_prompt'),
        ]);

        $this->openaiRepository->save($recording, [
            'transcript' => $result->text,
            'status' => TranscriptStatusEnum::TRANSCRIBING,
        ]);

        logger()->channel('ai')->info('Audio transcribed', ['recording' => $recording->id]);

        return $next($recording);
    }

    private function getRecordPath(Recording $recording): string
    {
        if (!file_exists($filepath = $recording->getAttribute('record'))) {
            throw new Exception('Record not found');
        }

        return $filepath;
    }
}

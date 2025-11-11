<?php

namespace App\Jobs\Leads;

use Exception;
use Throwable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Models\Leads\Recording;
use App\Enums\TranscriptStatusEnum;
use Illuminate\Support\Facades\Date;
use App\Services\Leads\OpenAIService;
use Illuminate\Queue\SerializesModels;
use App\Notifications\TranscriptMessage;
use Illuminate\Queue\InteractsWithQueue;
use App\Exceptions\RecordNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Leads\OpenAIRepository;
use Illuminate\Support\Facades\Notification;

class TranscriptionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 0;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $data,
        private readonly User $user,
    ) {
    }

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(OpenAIService $openAIService): void
    {
        logger()->channel('ai')->info('TranscriptionJob Started', $this->data);

        $recording = $this->getRecording();

        if (!$recording) {
            throw new RecordNotFoundException('Call not found');
        }

        $recording = $openAIService->analyze($recording);

        $this->notify($this->user, $recording, array_merge([
            'date_start' => Date::parse($recording->convertion->created_at)->format('Y-m-d'),
            'date_end' => Date::parse($recording->convertion->created_at)->format('Y-m-d'),
            'status' => $recording->status->value,
        ], $this->data));

        logger()->channel('ai')->info('TranscriptionJob Finished', $this->data);
    }

    public function getRecording(): ?Recording
    {
        /** @var OpenAIRepository $openAiRepository */
        $openAiRepository = app(OpenAIRepository::class);

        return $openAiRepository->find($this->data['id']);
    }

    public function notify(User $user, Recording $recording, array $data): void
    {
        /** @var OpenAIRepository $openAiRepository */
        $openAiRepository = app(OpenAIRepository::class);

        if ($user->id != 23) {
            $lead = $openAiRepository->lead($recording->id);
            Notification::send($user, new TranscriptMessage($lead, $data));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        logger()->channel('ai')->info('TranscriptionJob Failed', [
            'data' => $this->data,
            'exception' => $exception ? $exception->getMessage() : 'No exception message',
        ]);

        $recording = $this->getRecording();

        if ($recording) {
            $recording->update(['status' => TranscriptStatusEnum::FAILED]);

            $this->notify($this->user, $recording, array_merge([
                'date_start' => Date::parse($recording->convertion->created_at)->format('Y-m-d'),
                'date_end' => Date::parse($recording->convertion->created_at)->format('Y-m-d'),
                'status' => $recording->status->value,
            ], $this->data));
        }
    }
}

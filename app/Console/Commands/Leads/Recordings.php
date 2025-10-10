<?php

namespace App\Console\Commands\Leads;

use Carbon\Carbon;
use App\Models\Leads\Recording;
use Illuminate\Console\Command;
use App\Services\Leads\OpenAIService;

class Recordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recordings {call}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transcribe audio with whisper';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openai_service)
    {
        $this->info(Carbon::now()->format('H:i:s'));

        /** @var Recording $recording */
        $recording = Recording::query()->find($this->argument('call'));

        $recording = $openai_service->analyze($recording);

        $this->info($recording->toJson(JSON_PRETTY_PRINT));

        $this->info(Carbon::now()->format('H:i:s'));
    }
}

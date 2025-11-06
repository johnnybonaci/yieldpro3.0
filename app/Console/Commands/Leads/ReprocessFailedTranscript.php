<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\Buyer;
use App\Models\Leads\Recording;
use Illuminate\Console\Command;
use App\Enums\TranscriptStatusEnum;
use App\Jobs\Leads\TranscriptionJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class ReprocessFailedTranscript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reprocess-failed-transcript';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess failed transcriptions based on duration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reprocessing failed Transcript Status TRANSCRIBING based on duration...');

        $shortAudioThreshold = 1800;
        $mediumAudioThreshold = 3600;

        $shortAudioWaitTime = now()->subMinutes(5);
        $mediumAudioWaitTime = now()->subMinutes(10);
        $longAudioWaitTime = now()->subMinutes(15);

        $enabledBuyerIds = Buyer::where('enable_transcriptions', true)
            ->pluck('id')
            ->toArray();

        if (empty($enabledBuyerIds)) {
            $this->info('No buyers have transcriptions enabled. Skipping reprocessing.');

            return;
        }

        $this->info('Found ' . count($enabledBuyerIds) . ' buyers with transcriptions enabled.');

        $baseQuery = Recording::query()
            ->select('recordings.*', 'convertions.created_at')
            ->join('convertions', 'convertions.id', '=', 'recordings.id')
            ->where('recordings.status', TranscriptStatusEnum::TRANSCRIBING)
            ->whereIn('convertions.buyer_id', $enabledBuyerIds);

        $shortRecordings = (clone $baseQuery)
            ->where('convertions.durations', '<', $shortAudioThreshold)
            ->where('convertions.created_at', '<', $shortAudioWaitTime)
            ->get();

        $this->processRecordings($shortRecordings, 'short');

        $mediumRecordings = (clone $baseQuery)
            ->where('convertions.durations', '>=', $shortAudioThreshold)
            ->where('convertions.durations', '<=', $mediumAudioThreshold)
            ->where('convertions.created_at', '<', $mediumAudioWaitTime)
            ->get();

        $this->processRecordings($mediumRecordings, 'medium');

        $longRecordings = (clone $baseQuery)
            ->where('convertions.durations', '>', $mediumAudioThreshold)
            ->where('convertions.created_at', '<', $longAudioWaitTime)
            ->get();

        $this->processRecordings($longRecordings, 'long');

        $this->info('Reprocessing failed calls completed.');
    }

    /**
     * Process a collection of recordings.
     */
    private function processRecordings(Collection $recordings, string $type)
    {
        $count = $recordings->count();
        $this->info("Processing {$count} {$type} audio recordings...");

        $recordings->each(function (Recording $recording) {
            $user = Auth::loginUsingId(23, true);

            TranscriptionJob::dispatch([
                'id' => $recording->id,
                'date_start' => $recording->date_history,
                'date_end' => $recording->date_history,
            ], $user)->onQueue('transcript');
        });
    }
}

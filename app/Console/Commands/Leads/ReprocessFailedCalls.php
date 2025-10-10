<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\Recording;
use Illuminate\Console\Command;
use App\Jobs\Leads\UpdateCallJob;
use App\Enums\TranscriptStatusEnum;
use App\Jobs\Leads\TranscriptionJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class ReprocessFailedCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reprocess-failed-calls {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess failed calls.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reprocessing failed calls...');
        $date = $this->argument('date')
            ? Date::parse($this->argument('date'))->format('Y-m-d')
            : now()->startOfDay()->format('Y-m-d');

        Recording::query()
            ->where('status', TranscriptStatusEnum::FAILED)
            ->where('date_history', $date)
            ->get()
            ->each(function (Recording $recording) {
                $recording->update([
                    'status' => TranscriptStatusEnum::TRANSCRIBING,
                ]);

                TranscriptionJob::dispatch([
                    'id' => $recording->id,
                    'date_start' => $recording->date_history,
                    'date_end' => $recording->date_history,
                ], Auth::loginUsingId(23, $remember = true))->onQueue('transcript');

                // UpdateCallJob::dispatchSync($recording->id);
            });

        $this->info('Reprocessing failed calls completed.');
    }
}

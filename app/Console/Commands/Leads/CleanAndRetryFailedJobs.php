<?php

namespace App\Console\Commands\Leads;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class CleanAndRetryFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:clean-retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes specific failed jobs and retries all remaining failed jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Delete records from the failed_jobs table for specific queues
        DB::table('failed_jobs')->where('queue', 'like', '%whisper%')->delete();
        DB::table('failed_jobs')->where('queue', 'like', '%transcript%')->delete();

        // Execute the queue:retry all command
        $failedJobIds = DB::table('failed_jobs')->pluck('uuid')->toArray();

        if (!empty($failedJobIds)) {
            foreach ($failedJobIds as $id) {
                Artisan::call('queue:retry', ['id' => $id]);
            }
            $this->info('Failed jobs retried: ' . implode(', ', $failedJobIds));
        } else {
            $this->info('No failed jobs to retry.');
        }

        // Capture the command output and display it in the console
        $this->info('Specific jobs have been removed from failed_jobs.');
        $this->info('All failed jobs have been retried.');
        $this->line(Artisan::output());

        return 0;
    }
}

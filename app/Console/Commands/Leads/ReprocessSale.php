<?php

namespace App\Console\Commands\Leads;

use Illuminate\Support\Str;
use App\Models\Leads\Recording;
use Illuminate\Console\Command;
use App\Models\Leads\Convertion;
use App\Enums\TranscriptStatusEnum;
use App\Jobs\Leads\TranscriptionJob;
use Illuminate\Support\Facades\Auth;

class ReprocessSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reprocess-sale {phone} {start} {end}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = Auth()->user() ?? Auth::loginUsingId(23, $remember = true);
        Str::of($this->argument('phone'))->explode(',')->each(function ($phone) use ($user) {
            Convertion::select('recordings.id', 'convertions.phone_id')->join('recordings', 'convertions.id', '=', 'recordings.id')->where('phone_id', $phone)->where('durations', '>=', 10)->whereBetween('convertions.date_history', [$this->argument('start'), $this->argument('end')])
                ->get()
                ->each(function ($convertion) use ($user) {
                    TranscriptionJob::dispatch(['id' => $convertion->id, 'type' => $convertion->offer_id], $user)->onQueue('whisper');
                    Recording::find($convertion->id)->update(['status' => TranscriptStatusEnum::TRANSCRIBING->value]);
                    $this->info("Reprocessing Sale: {$convertion->id} - Phone: {$convertion->phone_id} - Start: {$this->argument('start')} - End: {$this->argument('end')}");
                });
        });
    }
}

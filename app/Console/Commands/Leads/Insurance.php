<?php

namespace App\Console\Commands\Leads;

use App\Models\Leads\Recording;
use Illuminate\Console\Command;
use App\Models\Leads\Convertion;
use Illuminate\Support\Facades\Log;
use App\Services\Leads\OpenAIServiceLegacy;

class Insurance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:insurance {start} {end}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIServiceLegacy $openAIService)
    {
        Convertion::select('recordings.id', 'convertions.phone_id', 'recordings.transcript', 'convertions.date_history')->join('recordings', 'convertions.id', '=', 'recordings.id')->whereNotNull('transcript')->whereBetween('convertions.date_history', [$this->argument('start'), $this->argument('end')])
            ->whereIn('convertions.offer_id', [20002, 20052])
            ->orderBy('convertions.date_history', 'ASC')
            ->get()
            ->each(function ($convertion) use ($openAIService) {
                $insurance = $openAIService->summaries($convertion->transcript, "Based on this transcript What was the caller's response when the agent asked, 'Do you currently have Medicare, Medicaid, Marketplace, VA coverage, or any other coverage?' If the caller's answer was positive, respond only with 'YES'. Otherwise, respond with 'NO'");
                $data['insurance'] = __toContains($insurance, 'YES') ? 1 : 2;
                Recording::find($convertion->id)->update(['insurance' => $data['insurance']]);
                Log::info('Result', ["Phone: {$convertion->phone_id} - Start: {$convertion->date_history} - Result: {$insurance}"]);
                sleep(5);
            });
    }
}

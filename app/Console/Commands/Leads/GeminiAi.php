<?php

namespace App\Console\Commands\Leads;

use Illuminate\Console\Command;
use Gemini\Laravel\Facades\Gemini;

class GeminiAi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gemini';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transcribe audio with Gemini';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $result = Gemini::geminiPro()->generateContent('Hello');
        $text = $result->text();
        $this->info($text);
    }
}

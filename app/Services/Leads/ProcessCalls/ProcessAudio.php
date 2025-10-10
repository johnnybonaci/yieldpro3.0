<?php

namespace App\Services\Leads\ProcessCalls;

use Closure;
use Exception;
use App\Models\Leads\Recording;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use App\Repositories\Leads\OpenAIRepository;

readonly class ProcessAudio
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
        logger()->channel('ai')->info('Processing audio', ['recording' => $recording->id]);

        if ($recording->getAttribute('record')) {
            return $next($recording);
        }

        $path = __toMakePath('app', 'recordings');
        $audio = $path . '/' . $recording->id . '.mp3';
        $url = str_replace("\0", '', trim($recording->url));
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $url = strtok($url, '?');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ]);

            $audioData = curl_exec($ch);

            if (curl_errno($ch)) {
                logger()->channel('ai')->info('Error', ['Audio not downloaded' => curl_error($ch)]);
            } else {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode == 200) {
                    file_put_contents($audio, $audioData);
                    logger()->channel('ai')->info('Success', ['Audio downloaded' => $audio]);
                } else {
                    logger()->channel('ai')->info('Error', ['Audio not downloaded' => $httpCode]);
                }
            }

            curl_close($ch);
        } else {
            logger()->channel('ai')->info('Error', ['Url not valid' => $url]);
        }

        $duration = $recording->convertion->durations ?? 0;

        if ($duration >= 60 * 60) {
            $processedAudio = $path . '/' . $recording->id . '-processed.webm';

            if (File::exists($processedAudio)) {
                File::delete($processedAudio);
            }

            $result = Process::timeout(300)->run("ffmpeg -i {$audio} -af silenceremove=stop_periods=-1:stop_duration=1:stop_threshold=-50dB -c:a libopus -b:a 12k {$processedAudio} 2>&1");

            $audio = $processedAudio;

            throw_if($result->failed(), new Exception($result->output()));
        }

        $this->openaiRepository->save($recording, [
            'record' => $audio,
        ]);

        logger()->channel('ai')->info('Audio processed', ['recording' => $recording->id]);

        return $next($recording);
    }
}

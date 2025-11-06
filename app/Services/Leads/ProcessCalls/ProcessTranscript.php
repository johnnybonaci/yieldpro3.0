<?php

namespace App\Services\Leads\ProcessCalls;

use Closure;
use Exception;
use Illuminate\Support\Str;
use App\Models\Leads\Recording;
use OpenAI\Laravel\Facades\OpenAI;
use App\Enums\TranscriptStatusEnum;
use App\Exceptions\RecordNotFoundException;
use App\Repositories\Leads\OpenAIRepository;

readonly class ProcessTranscript
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
        logger()->channel('ai')->info('Processing transcript', ['recording' => $recording->id]);

        if (is_null($recording->getAttribute('transcript'))) {
            throw new RecordNotFoundException('Transcript not found');
        }

        $data = ['type' => $recording->convertion->offer_id];

        /** @var string $text */
        $text = $recording->transcript;

        $analysis = $this->summaries($text, 'analysis', [
            'duration' => $recording->convertion->durations,
        ]);

        $analysis = Str::of($analysis)->trim()->replaceStart('```json', '')->replaceEnd('```', '')->toString();
        $analysis = json_validate($analysis) ? json_decode($analysis, true) : [];

        $analysisResult = validator($analysis, [
            'lead_type_and_participants' => 'present',
            'conversation_effectiveness' => 'present',
            'personal_data' => 'present',
            'call_issues' => 'present',
            'sentiment_analysis' => 'present',
            'mistreatment' => 'present',
            'existing_insurance' => 'present',
            'pre_qualification_analysis' => 'present',
            'sale_analysis' => 'present',
            'call_ending_analysis' => 'present',
            'conversation_effectiveness_result' => 'present',
            'call_issues_result' => 'present',
            'mistreatment_result' => 'present',
            'existing_insurance_result' => 'present',
            'existing_insurance_name' => 'present',
            'pre_qualification_analysis_result' => 'present',
            'sale_analysis_result' => 'present',
            'sale_analysis_details' => 'present',
            'call_ending_sooner_result' => 'present',
            'call_ending_sooner_reasons' => 'present',
            'call_ending_sooner_reason' => 'present',
        ])->validate();

        $data['multiple'] = $analysisResult;

        $data['multiple']['data'] = implode("\n", [
            $analysisResult['lead_type_and_participants'],
            $analysisResult['conversation_effectiveness'],
            $analysisResult['personal_data'],
            $analysisResult['call_issues'],
            $analysisResult['sentiment_analysis'],
            $analysisResult['mistreatment'],
            $analysisResult['existing_insurance'],
            $analysisResult['pre_qualification_analysis'],
            $analysisResult['sale_analysis'],
            $analysisResult['call_ending_analysis'],
        ]);

        $data['billable'] = match ($analysisResult['sale_analysis_result']) {
            'YES' => 1,
            'NO' => 0,
            'TO REVIEW' => 2,
            'N/A' => 0,
            default => 0,
        };

        $data['insurance'] = match ($data['multiple']['existing_insurance_result']) {
            'YES' => 1,
            'NO' => 2,
            'N/A' => 3,
            default => 3,
        };

        $data = $this->qaAnalysis($data, $text);

        $this->openaiRepository->save($recording, array_merge($data, [
            'status' => TranscriptStatusEnum::PROCESSED->value,
        ]));

        logger()->channel('ai')->info('Transcript processed', ['recording' => $recording->id]);

        return $next($recording);
    }

    public function summaries(string $text, string $promptName, array $promptData = []): string
    {
        $prompt = $this->getPrompt($promptName, $promptData);

        $text = Str::of($text)->prepend("Transcript:\n")->append("\n\n###\n\nJSON Output (Always respond a json):\n")->toString();

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $text],
            ],
            'temperature' => 0,
        ]);

        return $response['choices'][0]['message']['content'];
    }

    private function getPrompt(string $name, array $data = [], array $mergeData = []): string
    {
        return view("prompts.$name", $data, $mergeData)->render();
    }

    protected function qaAnalysis(array $data, string $text): array
    {
        if ($data['billable']) {
            return $data;
        }

        $qa = $this->summaries($text, 'qa');
        $qa = Str::of($qa)->trim()->replaceStart('```json', '')->replaceEnd('```', '')->toString();
        $qa = json_validate($qa) ? json_decode($qa, true) : [];

        $qa = validator($qa, [
            'ad_quality_error' => 'present',
            'not_interested' => 'present',
            'not_qualified' => 'present',
            'call_dropped' => 'present',
            'ivr' => 'present',
        ])->validate();

        $data['qa_status'] = [
            'ad_quality_error' => $qa['ad_quality_error'],
            'not_interested' => $qa['not_interested'],
            'not_qualified' => $qa['not_qualified'],
            'call_dropped' => $qa['call_dropped'],
            'reached_agent' => __toContains($data['multiple']['conversation_effectiveness_result'], 'YES') ?? false,
            'ivr' => $qa['ivr'],
        ];

        return $data;
    }
}

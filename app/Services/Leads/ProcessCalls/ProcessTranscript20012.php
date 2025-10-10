<?php

namespace App\Services\Leads\ProcessCalls;

use Closure;
use Exception;
use Illuminate\Support\Str;
use App\Models\Leads\Recording;
use OpenAI\Laravel\Facades\OpenAI;
use App\Enums\TranscriptStatusEnum;
use App\Repositories\Leads\OpenAIRepository;

readonly class ProcessTranscript20012
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
            throw new Exception('Transcript not found');
        }

        $data = ['type' => $recording->convertion->offer_id];

        /** @var string $text */
        $text = $recording->transcript;

        $analysis = $this->summaries($text, 'analysis');
        $analysis = Str::of($analysis)->trim()->replaceStart('```json', '')->replaceEnd('```', '')->toString();
        $analysis = json_validate($analysis) ? json_decode($analysis, true) : [];

        $data['multiple']['lead_type_and_participants'] = $analysis['lead_type_and_participants'];
        $data['multiple']['conversation_effectiveness'] = $analysis['conversation_effectiveness'];
        $data['multiple']['personal_data'] = $analysis['personal_data'];
        $data['multiple']['call_issues'] = $analysis['call_issues'];
        $data['multiple']['sentiment_analysis'] = $analysis['sentiment_analysis'];
        $data['multiple']['mistreatment'] = $analysis['mistreatment'];
        $data['multiple']['existing_insurance'] = $analysis['existing_insurance'];
        $data['multiple']['pre_qualification_analysis'] = $analysis['pre_qualification_analysis'];
        $data['multiple']['sale_analysis'] = $analysis['sale_analysis'];
        $data['multiple']['call_ending_analysis'] = $analysis['call_ending_analysis'];

        $data['multiple']['data'] = implode("\n", [
            $data['multiple']['lead_type_and_participants'],
            $data['multiple']['conversation_effectiveness'],
            $data['multiple']['personal_data'],
            $data['multiple']['call_issues'],
            $data['multiple']['sentiment_analysis'],
            $data['multiple']['mistreatment'],
            $data['multiple']['existing_insurance'],
            $data['multiple']['pre_qualification_analysis'],
            $data['multiple']['sale_analysis'],
            $data['multiple']['call_ending_analysis'],
        ]);

        $data['multiple']['conversation_effectiveness_result'] = $analysis['conversation_effectiveness_result'];
        $data['multiple']['call_issues_result'] = $analysis['call_issues_result'];
        $data['multiple']['mistreatment_result'] = $analysis['mistreatment_result'];
        $data['multiple']['existing_insurance_result'] = $analysis['existing_insurance_result'];
        $data['multiple']['existing_insurance_name'] = $analysis['existing_insurance_name'];
        $data['multiple']['pre_qualification_analysis_result'] = $analysis['pre_qualification_analysis_result'];
        $data['multiple']['sale_analysis_result'] = $analysis['sale_analysis_result'];
        $data['multiple']['sale_analysis_details'] = $analysis['sale_analysis_details'];
        $data['multiple']['call_ending_sooner_result'] = $analysis['call_ending_sooner_result'];
        $data['multiple']['call_ending_sooner_reasons'] = $analysis['call_ending_sooner_reasons'];

        $data['billable'] = match ($data['multiple']['sale_analysis_result']) {
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

    public function summaries(string $text, string $promptName): string
    {
        $prompt = $this->getPrompt($promptName);

        $text = Str::of($text)->prepend("Transcript:\n")->toString();

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

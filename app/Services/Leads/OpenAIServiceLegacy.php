<?php

namespace App\Services\Leads;

use Exception;
use App\Models\User;
use App\Models\Leads\Recording;
use OpenAI\Laravel\Facades\OpenAI;
use App\Enums\TranscriptStatusEnum;
use Illuminate\Support\Facades\Log;
use App\Notifications\TranscriptMessage;
use App\Repositories\Leads\OpenAIRepository;
use Illuminate\Support\Facades\Notification;

class OpenAIServiceLegacy
{
    public function __construct(
        private OpenAIRepository $openai_repository,
    ) {
    }

    /**
     * Transcribe Audio with whisper AI.
     */
    public function transcribe(array $data, User $user): array
    {
        $call = $this->openai_repository->find($data['id']);
        if (!$call) {
            return ["call doesn't exists"];
        }
        $text = $call->transcript;
        $data['audio'] = $call->record;
        $data['text'] = $text;

        try {
            if (!$call->record) {
                $path = __toMakePath('app', 'recordings');
                $audio = $path . '/' . $call->id . '.mp3';
                file_put_contents($audio, fopen($call->url, 'r'));
                $result = OpenAI::audio()->transcribe([
                    'model' => 'whisper-1',
                    'file' => fopen($audio, 'r'),
                    'response_format' => 'verbose_json',
                ]);
                $text = $result->text;
                $data['audio'] = $audio;
                $data['text'] = $text;
                $this->openai_repository->save($call, $data);
            }
            /*      TRANSCRIBT CONCLUSION */
            $data['billable'] = false;
            $data['multiple']['conclusion'] = 'The audio could not be analyzed. [no conversation took place]';
            $data['multiple']['detailed'] = 'The audio could not be analyzed. [no conversation took place]';
            $data['multiple']['sentiment_analysis'] = 'The audio could not be analyzed. [no conversation took place]';
            $data['insurance'] = 2;
            $drop_call = false;
            $conversation = $this->summaries($text, "Please review the call transcript and answer 'YES' if there is a conversation between [Person A] and [Person B] included in this transcription. otherwise, answer 'NO'. Please respond with a 'YES' or 'NO' only.");
            $conversation = __toContains($conversation, 'YES') ?? false;
            if ($conversation) {
                $drop_call = $this->summaries($text, "Please review the call transcript and answer 'YES' if the call between the two participants was completed without any interruptions or drop-offs otherwise answer 'NO'. Please respond with a 'YES' or 'NO' only.");
                $drop_call = __toContains($drop_call, 'YES') ?? false;
                $data['billable'] = false;

                if ($data['type'] === intval(20012)) {
                    $data['multiple']['conclusion'] = $this->summaries($text, "To summarize the transcript, we need a brief conclusion based on the following queries:
                    ### Asking About Debt

                    1. **Initial Inquiry**
                       Can you please tell me the total amount of debt you are currently dealing with?

                    2. **Clarification and Breakdown**
                       Just to confirm, could you break down the total debt by category? For instance, how much is from credit cards, medical bills, or any other types of debt?

                    ### Asking About Income

                    1. **Initial Inquiry**
                        To assist us in evaluating your situation more accurately, could you please provide the caller's monthly income before taxes and deductions?

                    2. **Clarification**
                       Is this income consistent every month, or does it fluctuate? If it varies, what would you estimate as your average monthly income?");

                    $data['multiple']['amount'] = $this->summaries($data['multiple']['conclusion'], "Please provide the total amount of debt the caller is currently dealing with. If the caller did not provide this information, please respond with 'N/A'.");
                } else {
                    $data['multiple']['conclusion'] = $this->summaries($text, "To summarize the transcript, we need a brief conclusion based on the following queries:
                        ### Already has insurance?
                        To determine if the caller is currently enrolling or already has insurance, consider the following questions:
                            Did the agent ask if the caller is looking for information about an ongoing enrollment process or if they already have existing coverage?
                            Did the agent inquire if the caller is currently in the process of enrolling for any coverage?
                        If the answer to any of these questions is yes, do not proceed. Otherwise, the caller is not currently enrolled in insurance.

                        ### Sale of the insurance policy
                        Was the sale of the insurance policy completed? To determine this, make sure the following four conditions are met:
                            There was no mention of a call back later to finish the process.
                            The call was not dropped in the middle of the conversation.
                            The caller is already insured and wants to maintain their current plan.
                            The caller was put on hold for a long time and dropped the call.
                            The caller was listening to on-hold music for some time and then dropped the call.
                        We need to ensure whether the policy sale was successfully completed or not.

                        ### Consent to the terms and conditions
                        Did the agent ask the question, 'Do you agree?' This means we need to verify whether the agent asked for the person's consent regarding any terms and conditions of the insurance policy.

                        ### Agreement to the terms and conditions
                        If the agent asks the person the question, 'Do you agree?' we need to confirm whether the person has given their agreement to the terms and conditions of the insurance policy.

                        ### Enrollment of the insurance plan
                        If the conversation progresses to discussing the details and possible enrollment of the insurance plan, and there is no indication that the sale has been completed or if the caller desires to cancel the enrollment at the end of the call leaving the process incomplete, do not consider it as a sale.");
                    $data['multiple']['insurance'] = $this->summaries($text, "Based on this transcript What was the caller's response when the agent asked, 'Do you currently have Medicare, Medicaid, Marketplace, VA coverage, or any other coverage?' If the caller's answer was negative and it has not been discovered in the course of the call to the contrary, respond only with 'NO'. Otherwise, respond with 'YES' if the caller's answer is positive and has not been found to the contrary.");
                    $data['insurance'] = __toContains($data['multiple']['insurance'], 'YES') ? 1 : 2;
                }

                /*    determines whether or not it was a sale */
                $data['multiple']['billable'] = $this->summaries($data['multiple']['conclusion'], "Has the customer completed and agreed to the enrollment? Answer 'YES' if the sale is finalized or say 'YES' if the agent facilitated the caller's subsidy application for their existing coverage. Say 'NO' if the discussion covered plan details without confirming the sale, if the customer already has insurance, seeks more benefits, confirmed details of an existing plan, or wants to cancel enrollment. Only 'YES' or 'NO' is needed. Please provide only a 'YES' or 'NO' response");
                /* DETAILED */
                $data['multiple']['detailed'] = $this->summaries($text, '## Summary of Transcription

            ### Organization of Summary
            The summary should be organized into sections, each with its own header. Avoid lengthy paragraphs and aim for a concise summary.

            ### Inclusion of Important Information
            Ensure that all important information is included in the summary. However, keep each section brief and to the point.

            ### Ignoring Repetitive or Pre-recorded Audio
            If repetitive or pre-recorded audio is detected, ignore those parts and focus only on the conversation between the two individuals.

            ### Conclusion
            Ensure conclusion is included. Keep this section brief and to the point. The conclusion should summarize the entire conversation and provide a clear understanding of the main points discussed. The conclusion should be no more than 4 lines.');
                /* DETAILED */
                $data['multiple']['sentiment_analysis'] = $this->summaries($data['multiple']['detailed'], 'Based on the transcript please determine the quality of the service provided by the call center/agent and the client’s satisfaction I need a short summary, 4 lines or less mandatory.');
                $data['billable'] = __toContains($data['multiple']['billable'], 'YES') ?? false;
            }
            $data['status'] = TranscriptStatusEnum::PROCESSED->value;

            /*  END TRANSCRIBT CONCLUSION */
            // QA Analysis

            if (!$data['billable']) {
                $ad_quality_error = $this->summaries($text, "Please review the transcript of the call and check to see if the caller showed interest in a product other than Medicare, ACA, Debt or Tax Debt as we do not offer any other type of service other than those. Answer 'YES' in that case or answer NO in case the person is interested in some of the products we offer. Only 'YES' or 'NO' is needed. Please provide only a 'YES' or 'NO' response.");
                $not_interested = $this->summaries($text, "Please review the call transcript and let me know if the caller expressed any disinterest in our insurance offerings. Could you determine if the caller was not interested in proceeding with any insurance plans based on their responses? Please respond with a simple 'YES'. Only 'YES' or 'NO' is needed. Please provide only a 'YES' or 'NO' response.");
                $not_qualified = $this->summaries($text, "Please review the call transcript and determine if the caller meets the eligibility requirements for our insurance plan based on the specific criteria and policies discussed during the call. Kindly answer with a 'YES' or 'NO' response. Can you confirm if the caller qualifies for the insurance plan based on the criteria discussed during the call?  Only 'YES' or 'NO' is needed. Please provide only a 'YES' or 'NO' response.");
                $call_droppped = $drop_call;
                $ivr = $this->summaries($text, "Please review the transcript of the call and indicate whether an Interactive Voice Response (IVR) system was used throughout the call. Specifically, I would like to know if the call began with an IVR and if it was unable to connect to a human agent. Only 'YES' or 'NO' is needed. Please provide only a 'YES' or 'NO' response.");
                $data['qa_status']['ad_quality_error'] = __toContains($ad_quality_error, 'YES') ?? false;
                $data['qa_status']['not_interested'] = __toContains($not_interested, 'YES') ?? false;
                $data['qa_status']['not_qualified'] = __toContains($not_qualified, 'YES') ?? false;
                $data['qa_status']['call_dropped'] = __toContains($call_droppped, 'YES') ?? false;
                $data['qa_status']['ivr'] = __toContains($ivr, 'YES') ?? false;
                $data['qa_status']['reached_agent'] = $conversation;
            }
            $this->openai_repository->save($call, $data);

            /*      SEND NOTIFICATION */
            $this->notify($user, $call, $data);
            /*      END SEND NOTIFICATION */

            return $data;
        } catch (Exception $e) {
            Log::error('Error Transcript:', [$e->getMessage()]);
            $status = TranscriptStatusEnum::FAILED->value;
            $call->status = $status;
            $data['status'] = $status;
            $call->save();
            $this->notify($user, $call, $data);

            return ['failed'];
        }
    }

    public function notify(User $user, Recording $call, array $data): void
    {
        if (!empty($user->id) && $user->id != 23) {
            $lead = $this->openai_repository->lead($call->id);
            Notification::send($user, new TranscriptMessage($lead, $data));
        }
    }

    /**
     * Transcribe Audio with whisper AI.
     */
    public function summaries(string $text, string $question): string
    {
        $summary = $question . PHP_EOL . ' """ ' . $text . ' """ ';

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4-0125-preview',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $summary],
            ],
            'temperature' => 1,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);
        $usage = $response['usage'];
        if ($usage) {
            $promptTokens = $usage['prompt_tokens'];
            $completionTokens = $usage['completion_tokens'];
            $totalTokens = $usage['total_tokens'];
            Log::info("Tokens utilizados V1 - Prompt: $promptTokens, Completion: $completionTokens, Total: $totalTokens");
        }

        return $response['choices'][0]['message']['content'];
    }

    /**
     * Segment conversations.
     */
    public function segment(array $segments): string
    {
        $response = '';
        foreach ($segments as $segment) {
            $response .= '(';
            $response .= __toMinutes($segment->start);
            $response .= ' - ';
            $response .= __toMinutes($segment->end);
            $response .= ')';

            $response .= PHP_EOL;
            $response .= $segment->text;
            $response .= PHP_EOL;
            $response .= PHP_EOL;
        }

        return $response;
    }
}

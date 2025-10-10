<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadsSummaryNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    public function build()
    {
        return $this->subject('Alert: Leads Limit Exceeded')
            ->view('emails.leads_summary')
            ->with([
                'results' => $this->results,
            ]);
    }
}

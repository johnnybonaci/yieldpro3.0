<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeadsLimitExceeded extends Notification
{
    public $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $message = new MailMessage();

        $message->subject('Urgent: Unprocessed Leads Exceeding Limit')
            ->greeting('Hello,')
            ->line('The following pub_ids have exceeded the limit of 50 leads:');

        foreach ($this->results as $result) {
            $message->line("- Pub ID {$result->pub_id} currently has {$result->total} leads queued, which exceeds the limit of 50.");
        }

        $message->line('Please address this issue immediately to ensure timely processing.')
            ->salutation('Thanks.')
            ->salutation('YieldPro Alerts');

        return $message;
    }
}

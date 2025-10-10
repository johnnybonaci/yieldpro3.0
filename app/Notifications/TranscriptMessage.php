<?php

namespace App\Notifications;

use App\Models\Leads\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TranscriptMessage extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private Lead $lead, private array $data)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->setArray($notifiable);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->setArray($notifiable)))->onConnection('database')
            ->onQueue('broadcasts');
    }

    public function setArray(object $notifiable): array
    {
        return [
            'lead' => [
                'phone' => $this->lead->phone,
            ],
            'user' => [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
                'id' => $notifiable->id,
            ],
            'date_start' => $this->data['date_start'],
            'date_end' => $this->data['date_end'],
            'convertions_id' => $this->data['id'],
            'url' => '/leads/calls?date_start=' . $this->data['date_start'] . '&date_end=' . $this->data['date_end'] . '&convertions_id=' . $this->data['id'],
            'status' => $this->data['status'] == 1 ? true : false,
            'message' => $this->data['status'] == 1 ? 'Transcription ready!' : 'Transcription failed!',
            'svg' => $this->data['status'] == 1 ? 'file-audio.svg' : 'file-failed.svg',
        ];
    }
}

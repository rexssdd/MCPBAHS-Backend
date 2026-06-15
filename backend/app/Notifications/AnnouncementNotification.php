<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  string[]  $channels  Delivery channels for this instance.
     *                              Defaults to ['database'] (in-app).
     *                              Pass ['mail'] when sending from SendEmailJob.
     *                              This avoids duplicating the notification class
     *                              for each channel while keeping channel selection
     *                              explicit at the call site.
     */
    public function __construct(
        protected Announcement $announcement,
        protected array $channels = ['database'],
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * CNS-04 fix: the original body was boilerplate placeholder text
     * ("The introduction to the notification…"). Now uses actual announcement
     * content so emails are meaningful if the 'mail' channel is added to via().
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->announcement->title)
            ->greeting('School Announcement')
            ->line($this->announcement->message)
            ->line('Urgency: ' . $this->announcement->urgency?->value);
    }

    /**
     * Get the array representation of the notification.
     *
     * CNS-04 fix: returns real announcement data as a fallback. The empty array
     * original would produce a useless notification record if this path were hit.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'uuid'      => $this->announcement->uuid,
            'title'     => $this->announcement->title,
            'message'   => $this->announcement->message,
            'urgency'   => $this->announcement->urgency,
            'posted_at' => $this->announcement->posted_at,
        ];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'uuid' => $this->announcement->uuid,
            'title' => $this->announcement->title,
            'message' => $this->announcement->message,
            'urgency' => $this->announcement->urgency,
            'posted_at' => $this->announcement->posted_at,
        ]);
    }
}
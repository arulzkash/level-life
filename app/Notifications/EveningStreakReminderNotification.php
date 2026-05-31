<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class EveningStreakReminderNotification extends Notification
{
    /**
     * @return array<class-string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Keep your streak alive')
            ->body('You have not completed a quest today. Finish one before the day ends.')
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag('level-life-evening-streak-reminder')
            ->data([
                'url' => url('/dashboard'),
            ])
            ->options([
                'TTL' => 3600,
            ]);
    }
}

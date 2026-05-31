<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MorningReminderNotification extends Notification
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
            ->title('Start your Level Life run')
            ->body('Pick one quest and make today count.')
            ->icon('/icons/icon-192.png')
            ->badge('/icons/notification-badge-96x96.png')
            ->tag('level-life-morning-reminder')
            ->data([
                'url' => url('/dashboard'),
            ])
            ->options([
                'TTL' => 3600,
            ]);
    }
}

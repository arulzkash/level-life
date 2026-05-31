<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestWebPushNotification extends Notification
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
            ->title('Level Life notifications enabled')
            ->body('This is a test notification from Level Life.')
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag('level-life-test')
            ->data([
                'url' => url('/dashboard'),
            ])
            ->options([
                'TTL' => 300,
            ]);
    }
}

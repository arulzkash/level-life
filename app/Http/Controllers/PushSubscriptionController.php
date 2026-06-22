<?php

namespace App\Http\Controllers;

use App\Notifications\TestWebPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PushSubscriptionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['nullable', 'string', 'max:500'],
        ]);

        $endpoint = $validated['endpoint'] ?? null;
        $endpointSubscribed = false;

        if ($endpoint) {
            $endpointSubscribed = $request->user()
                ->pushSubscriptions()
                ->where('endpoint', $endpoint)
                ->exists();
        }

        return response()->json([
            'endpointSubscribed' => $endpointSubscribed,
            'subscriptionCount' => $request->user()->pushSubscriptions()->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:500'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:50'],
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['contentEncoding'] ?? 'aes128gcm',
        );

        return response()->json([
            'subscribed' => true,
            'subscriptionCount' => $request->user()->pushSubscriptions()->count(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->json([
            'subscribed' => false,
            'subscriptionCount' => $request->user()->pushSubscriptions()->count(),
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        if (! config('webpush.vapid.public_key') || ! config('webpush.vapid.private_key')) {
            return response()->json([
                'message' => 'VAPID keys are not configured.',
            ], 422);
        }

        if (! $request->user()->pushSubscriptions()->exists()) {
            return response()->json([
                'message' => 'No push subscription is registered for this user.',
            ], 422);
        }

        try {
            $request->user()->notify(new TestWebPushNotification);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to send test notification.',
            ], 500);
        }

        return response()->json([
            'message' => 'Test notification sent.',
            'subscriptionCount' => $request->user()->pushSubscriptions()->count(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'morning_enabled' => ['required', 'boolean'],
            'evening_enabled' => ['required', 'boolean'],
        ]);

        $settings = $request->user()->notificationSetting()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'morning_enabled' => $validated['morning_enabled'],
                'evening_enabled' => $validated['evening_enabled'],
            ],
        );

        return response()->json([
            'settings' => [
                'morning_enabled' => $settings->morning_enabled,
                'evening_enabled' => $settings->evening_enabled,
            ],
        ]);
    }
}

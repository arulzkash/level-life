<?php

namespace App\Http\Controllers;

use App\Notifications\TestWebPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
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
}

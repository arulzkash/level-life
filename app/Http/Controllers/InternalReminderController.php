<?php

namespace App\Http\Controllers;

use App\Services\ReminderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalReminderController extends Controller
{
    public function cronMorning(Request $request, ReminderNotificationService $reminders): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($reminders->sendMorningReminders());
    }

    public function cronEvening(Request $request, ReminderNotificationService $reminders): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($reminders->sendEveningReminders());
    }

    public function morning(Request $request, ReminderNotificationService $reminders): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return response()->json($reminders->sendMorningReminders());
    }

    public function evening(Request $request, ReminderNotificationService $reminders): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return response()->json($reminders->sendEveningReminders());
    }

    private function tokenIsValid(Request $request): bool
    {
        $configuredToken = (string) config('services.internal_reminders.token');

        if ($configuredToken === '') {
            return false;
        }

        $providedToken = (string) $request->bearerToken();

        if ($providedToken === '') {
            $providedToken = (string) $request->header('X-Internal-Token');
        }

        if ($providedToken === '') {
            $providedToken = (string) $request->query('token');
        }

        return hash_equals($configuredToken, $providedToken);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use App\Support\CacheBuster;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $notificationSettings = $request->user()->notificationSetting()->firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'morning_enabled' => false,
                'evening_enabled' => false,
            ],
        );

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'notifications' => [
                'vapidPublicKey' => config('webpush.vapid.public_key'),
                'subscriptionCount' => $request->user()->pushSubscriptions()->count(),
                'showTestNotification' => app()->environment('local'),
                'settings' => [
                    'morning_enabled' => $notificationSettings->morning_enabled,
                    'evening_enabled' => $notificationSettings->evening_enabled,
                ],
            ],
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->profile ?: Profile::query()->firstOrCreate(['user_id' => $user->id]);
        $validated = $request->validated();
        $oldUsername = $user->username;

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $profile->bio = $validated['bio'] ?? null;
        $profile->save();

        CacheBuster::invalidateNavUser($user->id);
        CacheBuster::invalidateNavProfile($user->id);
        CacheBuster::invalidateLeaderboardDaily();
        CacheBuster::invalidatePublicProfileSummary($user->id);
        CacheBuster::invalidatePublicProfileLookup($oldUsername);
        CacheBuster::invalidatePublicProfileLookup($user->username);

        return Redirect::route('profile.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

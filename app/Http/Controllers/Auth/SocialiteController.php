<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToGithub()
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleGithubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Throwable $e) {
            return redirect('/')->with('error', 'GitHub login failed. Please try again.');
        }

        $user = User::updateOrCreate(
            ['github_id' => $githubUser->getId()],
            [
                'name'   => $githubUser->getName() ?? $githubUser->getNickname(),
                'email'  => $githubUser->getEmail(),
                'avatar' => $githubUser->getAvatar(),
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    }
}

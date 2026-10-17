<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->home($request));
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($this->home($request))->with('success', 'Email berhasil diverifikasi.');
    }

    protected function home(\Illuminate\Http\Request $request): string
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return route('super-admin.dashboard');
        }

        if ($user?->isBarber()) {
            return route('barber.dashboard');
        }

        return route('admin.dashboard');
    }
}

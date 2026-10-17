<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended($this->homeFor($request->user()))
            : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }

    protected function homeFor($user): string
    {
        if ($user?->isSuperAdmin()) {
            return route('super-admin.dashboard');
        }

        if ($user?->isBarber()) {
            return route('barber.dashboard');
        }

        return route('admin.dashboard');
    }
}

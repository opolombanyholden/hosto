<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * User profile management (shared across all environments).
 */
final class ProfileController
{
    public function show(Request $request, TwoFactorService $twoFactor): View
    {
        $user = $request->user();
        $environment = $this->detectEnvironment($request);

        return view('auth.profile', [
            'user' => $user,
            'environment' => $environment,
            'twoFactorEnabled' => $twoFactor->isEnabled($user),
        ]);
    }

    public function updateInfo(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($data);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'fields' => array_keys($data),
        ]);

        return back()->with('success', 'Informations mises a jour.');
    }

    public function updatePassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'fields' => ['password'],
        ]);

        return back()->with('success', 'Mot de passe modifie avec succes.');
    }

    private function detectEnvironment(Request $request): string
    {
        if ($request->is('admin*')) {
            return 'admin';
        }
        if ($request->is('pro*')) {
            return 'pro';
        }

        return 'compte';
    }
}

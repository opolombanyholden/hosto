<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureProValidated.
 *
 * For professional accounts: checks that the admin validation
 * has been completed (pro_validation_status === 'approved').
 *
 * Usage:
 *   ->middleware('pro.validated')
 *
 * Allows access to dashboard and profile even if not yet validated,
 * but blocks access to functional features (patients, consultations, etc.).
 *
 * @see docs/adr/0012-verification-compte-workflow.md
 */
final class EnsureProValidated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/pro/connexion');
        }

        if ($user->pro_validation_status !== 'approved') {
            $status = $user->pro_validation_status;
            $message = match ($status) {
                'submitted' => 'Votre dossier est en cours de validation par nos equipes.',
                'rejected' => 'Votre dossier a ete rejete. Consultez votre profil pour plus de details.',
                'suspended' => 'Votre dossier est suspendu. Des documents complementaires sont requis.',
                default => 'Veuillez soumettre votre dossier professionnel pour activer votre compte.',
            };

            return redirect('/pro')->with('pro_status', $message);
        }

        return $next($request);
    }
}

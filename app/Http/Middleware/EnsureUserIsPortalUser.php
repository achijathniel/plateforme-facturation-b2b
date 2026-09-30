<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsPortalUser
{
    /**
     * Intercepte la requête et s'assure que l'utilisateur connecté appartient à une entreprise
     * cliente avec un rôle autorisé (ACCOUNTANT ou CLIENT).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('portal.login');
        }

        if (! in_array($user->role, [UserRole::ACCOUNTANT, UserRole::CLIENT], true) || ! $user->organization_id) {
            abort(Response::HTTP_FORBIDDEN, 'Accès réservé aux membres des entreprises clientes.');
        }

        return $next($request);
    }
}

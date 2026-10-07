<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class PortalAuthController extends Controller
{
    /**
     * Affiche la page de connexion au portail entreprise / comptable.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isPortalUser()) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Portal/Auth/Login');
    }

    /**
     * Traite la tentative d'authentification sur le portail entreprise.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides ou mot de passe incorrect.',
            ]);
        }

        $user = Auth::user();

        // Sécurité Multi-Tenancy : L'utilisateur doit appartenir à une entreprise avec un rôle portail valide
        if (! $user->isPortalUser()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Accès refusé : Cet espace est strictement réservé aux entreprises clientes.',
            ]);
        }

        // Régénération de la session pour contrer la fixation de session
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    /**
     * Déconnecte l'utilisateur du portail et détruit la session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}

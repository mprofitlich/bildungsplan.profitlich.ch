<?php

namespace App\Http\Controllers;

use App\Mail\LoginLinkMail;
use App\Models\Domain;
use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Einloggen per Link aus einer Mail (ADR 0002). */
class LoginController extends Controller
{
    /** Höchstzahl angeforderter Links je Adresse in zehn Minuten. */
    private const LINKS_JE_ADRESSE = 3;

    public function anfordern(Request $request): JsonResponse
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $request->merge(['email' => $email]);
        $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        if (! Domain::erlaubt($email)) {
            $domains = Domain::orderBy('name')->pluck('name')->join(', ', ' und ');
            throw ValidationException::withMessages([
                'email' => "Einloggen können nur Adressen von {$domains}.",
            ]);
        }

        $schluessel = "login-link:{$email}";
        if (RateLimiter::tooManyAttempts($schluessel, self::LINKS_JE_ADRESSE)) {
            $minuten = (int) ceil(RateLimiter::availableIn($schluessel) / 60);
            throw ValidationException::withMessages([
                'email' => "Für diese Adresse wurden eben mehrere Links verschickt. Neuer Versuch in {$minuten} Minuten.",
            ]);
        }
        RateLimiter::hit($schluessel, 600);

        $token = LoginLink::erstellen($email);
        Mail::to($email)->send(new LoginLinkMail(route('login.zeigen', $token)));

        return response()->json(['meldung' => "Der Link ist unterwegs an {$email}."]);
    }

    /**
     * Zeigt eine Seite mit Knopf statt sofort einzuloggen: Virenscanner von Mailsystemen
     * öffnen Links vorab und würden den einmaligen Link sonst verbrauchen.
     */
    public function zeigen(string $token): View
    {
        return view('login', ['gueltig' => LoginLink::gueltig($token) !== null, 'token' => $token]);
    }

    public function einloesen(Request $request, string $token): RedirectResponse|View
    {
        $link = LoginLink::gueltig($token);
        // Bedingtes Update, damit ein doppelt abgeschickter Knopf den Link nicht zweimal einlöst.
        $eingeloest = $link && LoginLink::whereKey($link->id)->whereNull('eingeloest_am')->update(['eingeloest_am' => now()]);
        if (! $eingeloest || ! Domain::erlaubt($link->email)) {
            return view('login', ['gueltig' => false, 'token' => $token]);
        }

        $user = User::firstOrCreate(['email' => $link->email], ['domain' => Domain::von($link->email)]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/');
    }

    public function abmelden(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['meldung' => 'Abgemeldet.']);
    }
}

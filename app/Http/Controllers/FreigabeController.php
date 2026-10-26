<?php

namespace App\Http\Controllers;

use App\Http\Resources\KursResource;
use App\Mail\FreigabeMail;
use App\Models\Domain;
use App\Models\Kurs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/** Freigaben eines Kurses an Mailadressen derselben Domain (ADR 0002). */
class FreigabeController extends Controller
{
    public function store(Request $request, Kurs $kurs): KursResource
    {
        Gate::authorize('verwalten', $kurs);
        $email = $this->adresse($request);
        $ich = $request->user();

        if (Domain::von($email) !== $ich->domain) {
            throw ValidationException::withMessages(['email' => "Freigeben lässt sich nur an Adressen von {$ich->domain}."]);
        }
        if ($email === $ich->email) {
            throw ValidationException::withMessages(['email' => 'Der Kurs gehört dir bereits.']);
        }

        $freigabe = $kurs->freigaben()->firstOrCreate(['email' => $email]);
        if ($freigabe->wasRecentlyCreated) {
            Mail::to($email)->send(new FreigabeMail($kurs));
        }

        return new KursResource($kurs->load('freigaben'));
    }

    public function destroy(Request $request, Kurs $kurs): KursResource
    {
        Gate::authorize('verwalten', $kurs);
        $kurs->freigaben()->where('email', $this->adresse($request))->delete();

        return new KursResource($kurs->load('freigaben'));
    }

    private function adresse(Request $request): string
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        return $request->validate(['email' => ['required', 'email:rfc', 'max:255']])['email'];
    }
}

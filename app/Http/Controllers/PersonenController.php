<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Vorschläge für die Freigabe: bekannte Personen der eigenen Domain. */
class PersonenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $ich = $request->user();
        $suche = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('suche')));

        $adressen = User::where('domain', $ich->domain)
            ->whereKeyNot($ich->id)
            ->when($suche !== '', fn ($q) => $q->where('email', 'like', "%{$suche}%"))
            ->orderBy('email')
            ->limit(10)
            ->pluck('email');

        return response()->json($adressen);
    }
}

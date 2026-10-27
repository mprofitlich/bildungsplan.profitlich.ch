<?php

namespace App\Http\Controllers;

use App\Models\Kurs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Alle Kurse der eigenen Domain mit «Im Curriculum zeigen», nur zum Lesen. */
class CurriculumController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $domain = $request->user()->domain;

        $kurse = Kurs::where('im_curriculum', true)
            ->whereHas('besitzer', fn ($q) => $q->where('domain', $domain))
            ->with('besitzer')
            ->orderBy('titel')
            ->get()
            ->map(fn (Kurs $kurs) => [
                'id' => $kurs->id,
                'titel' => $kurs->titel,
                'beschrieb' => $kurs->beschrieb,
                'kriterien' => Kurs::kriterienVon($kurs->kriterien),
                'ziele' => $kurs->ziele,
                'besitzer' => $kurs->besitzer->email,
            ]);

        return response()->json(['domain' => $domain, 'kurse' => $kurse]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\KursResource;
use App\Models\Kurs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/** Kurse der eingeloggten Person: eigene und freigegebene. */
class KursController extends Controller
{
    private const REGELN = [
        'titel' => ['present', 'nullable', 'string', 'max:255'],
        'beschrieb' => ['present', 'nullable', 'string', 'max:20000'],
        'ziele' => ['present', 'array', 'max:1000'],
        'ziele.*' => ['string', 'regex:/^[a-z0-9.-]{1,32}$/'],
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        return KursResource::collection($this->liste($request));
    }

    public function show(Kurs $kurs): KursResource
    {
        Gate::authorize('bearbeiten', $kurs);

        return new KursResource($kurs);
    }

    public function store(Request $request): JsonResponse
    {
        $daten = $request->validate(['id' => ['required', 'uuid', 'unique:kurse,id'], ...self::REGELN]);
        $kurs = $request->user()->kurse()->create($this->bereinigt($daten));

        return (new KursResource($kurs))->response()->setStatusCode(201);
    }

    /**
     * Übernimmt die Kurse aus dem Browser beim Login. Bekannte Ids bleiben, wie sie in der
     * Datenbank stehen; der leere Anfangskurs wird übergangen, sonst brächte jedes Gerät einen mit.
     */
    public function import(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'kurse' => ['present', 'array', 'max:500'],
            'kurse.*.id' => ['required', 'uuid'],
            ...collect(self::REGELN)->mapWithKeys(fn ($regel, $feld) => ["kurse.*.{$feld}" => $regel])->all(),
        ]);
        $ich = $request->user();

        foreach ($request->input('kurse') as $daten) {
            if (Kurs::istLeer($daten)) {
                continue;
            }
            $vorhanden = Kurs::find($daten['id']);
            if ($vorhanden?->darfBearbeiten($ich)) {
                continue;
            }
            // Eine fremde Id bekommt der Kurs nicht; er wird als neuer angelegt.
            $ich->kurse()->create([
                ...$this->bereinigt($daten),
                'id' => $vorhanden ? (string) Str::uuid() : $daten['id'],
            ]);
        }

        return KursResource::collection($this->liste($request));
    }

    /**
     * Speichert Titel, Beschrieb und Ziele. Abgelehnt wird, wenn jemand anderes die Sperre hält (423)
     * oder der Kurs seit der mitgeschickten Version geändert wurde (409); beide Antworten enthalten
     * den aktuellen Stand. Wer speichert, hält danach die Sperre.
     */
    public function update(Request $request, Kurs $kurs): KursResource|JsonResponse
    {
        Gate::authorize('bearbeiten', $kurs);
        $daten = $request->validate(['version' => ['required', 'integer'], ...self::REGELN]);
        $ich = $request->user();

        return DB::transaction(function () use ($kurs, $daten, $ich) {
            $kurs = Kurs::lockForUpdate()->findOrFail($kurs->id);

            if ($kurs->gesperrtFuer($ich)) {
                return (new KursResource($kurs))->response()->setStatusCode(423);
            }
            if ($kurs->version !== $daten['version']) {
                return (new KursResource($kurs))->response()->setStatusCode(409);
            }

            $kurs->fill($this->bereinigt($daten));
            $kurs->version++;
            $kurs->sperren($ich);
            $kurs->save();

            return new KursResource($kurs);
        });
    }

    public function destroy(Kurs $kurs): JsonResponse
    {
        Gate::authorize('verwalten', $kurs);
        $kurs->delete();

        return response()->json(null, 204);
    }

    public function curriculum(Request $request, Kurs $kurs): KursResource
    {
        Gate::authorize('verwalten', $kurs);
        $kurs->im_curriculum = $request->validate(['sichtbar' => ['required', 'boolean']])['sichtbar'];
        $kurs->save();

        return new KursResource($kurs);
    }

    /** Setzt oder erneuert die Bearbeitungssperre; 423, solange jemand anderes sie hält. */
    public function sperren(Request $request, Kurs $kurs): KursResource|JsonResponse
    {
        Gate::authorize('bearbeiten', $kurs);
        $ich = $request->user();

        return DB::transaction(function () use ($kurs, $ich) {
            $kurs = Kurs::lockForUpdate()->findOrFail($kurs->id);
            if ($kurs->gesperrtFuer($ich)) {
                return (new KursResource($kurs))->response()->setStatusCode(423);
            }
            $kurs->sperren($ich);
            // Die Sperre ist keine Änderung am Kurs; «zuletzt geändert» bleibt stehen.
            $kurs->timestamps = false;
            $kurs->save();

            return new KursResource($kurs);
        });
    }

    public function entsperren(Request $request, Kurs $kurs): KursResource
    {
        Gate::authorize('bearbeiten', $kurs);
        Kurs::whereKey($kurs->id)->where('gesperrt_von', $request->user()->id)
            ->update(['gesperrt_von' => null, 'gesperrt_bis' => null]);

        return new KursResource($kurs->refresh());
    }

    private function liste(Request $request)
    {
        return Kurs::bearbeitbarFuer($request->user())
            ->with(['besitzer', 'sperrer', 'freigaben'])
            ->orderBy('created_at')
            ->get();
    }

    /** Leere Felder kommen als `null` an; gespeichert werden sie als leerer Text. */
    private function bereinigt(array $daten): array
    {
        return [
            ...(isset($daten['id']) ? ['id' => $daten['id']] : []),
            'titel' => $daten['titel'] ?? '',
            'beschrieb' => $daten['beschrieb'] ?? '',
            'ziele' => array_values(array_unique($daten['ziele'])),
        ];
    }
}

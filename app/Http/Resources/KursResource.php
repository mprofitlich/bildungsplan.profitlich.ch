<?php

namespace App\Http\Resources;

use App\Models\Kurs;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ein Kurs aus Sicht der eingeloggten Person: Freigaben sieht nur, wer den Kurs besitzt.
 *
 * @mixin Kurs
 */
class KursResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ich = $request->user();
        $besitzer = $this->istBesitzer($ich);
        $sperre = $this->gesperrt_bis?->isFuture() && $this->gesperrt_von !== null;

        return [
            'id' => $this->id,
            'nummer' => $this->nummer,
            'titel' => $this->titel,
            'beschrieb' => $this->beschrieb,
            'kriterien' => Kurs::kriterienVon($this->kriterien),
            'ziele' => $this->ziele,
            'version' => $this->version,
            'imCurriculum' => $this->im_curriculum,
            'besitzer' => $this->besitzer->email,
            'istBesitzer' => $besitzer,
            'freigaben' => $besitzer ? $this->freigaben->pluck('email')->sort()->values() : [],
            'sperre' => $sperre ? [
                'email' => $this->sperrer?->email,
                'eigene' => $this->gesperrt_von === $ich->id,
                'bis' => $this->gesperrt_bis->toIso8601String(),
            ] : null,
        ];
    }
}

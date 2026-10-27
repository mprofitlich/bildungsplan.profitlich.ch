<?php

namespace App\Models;

use Database\Factories\KursFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Ein Kurs mit den Leistungszielen, die er abdeckt.
 *
 * @property string $id
 * @property int $nummer
 * @property int $besitzer_id
 * @property string $titel
 * @property string $beschrieb
 * @property array<string, string>|null $kriterien
 * @property list<string> $ziele
 * @property bool $im_curriculum
 * @property int $version
 * @property int|null $gesperrt_von
 * @property Carbon|null $gesperrt_bis
 * @property-read User $besitzer
 * @property-read User|null $sperrer
 */
#[Fillable(['id', 'titel', 'beschrieb', 'kriterien', 'ziele'])]
class Kurs extends Model
{
    /** @use HasFactory<KursFactory> */
    use HasFactory, HasUuids;

    /** So lange gilt eine Sperre ohne Erneuerung (ADR 0003). */
    public const SPERRE_SEKUNDEN = 120;

    /** Schlüssel der Beurteilungskriterien; die Bezeichnungen stehen im Frontend (src/lib/kriterien.js). */
    public const KRITERIEN = ['entwicklung', 'gestaltungskonzept', 'umsetzung', 'vermittlung'];

    protected $table = 'kurse';

    protected $attributes = ['im_curriculum' => false, 'version' => 1];

    /**
     * Vergibt die nächste Nummer. Legen zwei Personen im selben Augenblick einen Kurs an, scheitert
     * eine der beiden am eindeutigen Index; der Browser versucht es mit der nächsten Änderung erneut.
     */
    protected static function booted(): void
    {
        static::creating(function (Kurs $kurs) {
            $kurs->nummer ??= (static::max('nummer') ?? 0) + 1;
        });
    }

    protected function casts(): array
    {
        return [
            'nummer' => 'integer',
            'kriterien' => 'array',
            'ziele' => 'array',
            'im_curriculum' => 'boolean',
            'version' => 'integer',
            'gesperrt_bis' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function besitzer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'besitzer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function sperrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gesperrt_von');
    }

    /** @return HasMany<Freigabe, $this> */
    public function freigaben(): HasMany
    {
        return $this->hasMany(Freigabe::class);
    }

    /**
     * Kurse, die eine Person besitzt oder mitbearbeiten darf.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBearbeitbarFuer(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('besitzer_id', $user->id)
            ->orWhereHas('freigaben', fn (Builder $f) => $f->where('email', $user->email)));
    }

    public function istBesitzer(User $user): bool
    {
        return $this->besitzer_id === $user->id;
    }

    public function darfBearbeiten(User $user): bool
    {
        return $this->istBesitzer($user) || $this->freigaben()->where('email', $user->email)->exists();
    }

    /** Ob gerade eine andere Person die Sperre hält. */
    public function gesperrtFuer(User $user): bool
    {
        return $this->gesperrt_von !== null
            && $this->gesperrt_von !== $user->id
            && $this->gesperrt_bis?->isFuture();
    }

    /** Setzt oder erneuert die Sperre; ohne Speichern. */
    public function sperren(User $user): void
    {
        $this->gesperrt_von = $user->id;
        $this->gesperrt_bis = now()->addSeconds(self::SPERRE_SEKUNDEN);
    }

    /**
     * Alle Beurteilungskriterien mit Text, fehlende leer; unbekannte Schlüssel fallen weg.
     *
     * @param  array<string, string|null>|null  $kriterien
     * @return array<string, string>
     */
    public static function kriterienVon(?array $kriterien): array
    {
        return collect(self::KRITERIEN)
            ->mapWithKeys(fn (string $schluessel) => [$schluessel => (string) ($kriterien[$schluessel] ?? '')])
            ->all();
    }

    /** Ob ein Kurs nur den leeren Anfangsstand hat, den die Anwendung von sich aus anlegt. */
    public static function istLeer(array $daten): bool
    {
        return in_array(trim($daten['titel'] ?? ''), ['', 'Neuer Kurs'], true)
            && trim($daten['beschrieb'] ?? '') === ''
            && trim(implode('', self::kriterienVon($daten['kriterien'] ?? null))) === ''
            && ($daten['ziele'] ?? []) === [];
    }
}

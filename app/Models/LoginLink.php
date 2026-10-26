<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Ein einmal verwendbarer Link zum Einloggen.
 *
 * @property int $id
 * @property string $email
 * @property string $token_hash
 * @property Carbon $gueltig_bis
 * @property Carbon|null $eingeloest_am
 */
#[Fillable(['email', 'token_hash', 'gueltig_bis'])]
class LoginLink extends Model
{
    public const GUELTIG_MINUTEN = 15;

    protected function casts(): array
    {
        return [
            'gueltig_bis' => 'datetime',
            'eingeloest_am' => 'datetime',
        ];
    }

    /**
     * Legt einen Link an und liefert das Token, das in die Mail gehört.
     * Gespeichert wird nur sein Hash, damit ein Datenbankauszug keine gültigen Links enthält.
     */
    public static function erstellen(string $email): string
    {
        $token = Str::random(48);
        static::create([
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'gueltig_bis' => now()->addMinutes(self::GUELTIG_MINUTEN),
        ]);

        return $token;
    }

    /** Findet den Link zu einem Token, solange er gültig und nicht eingelöst ist. */
    public static function gueltig(string $token): ?self
    {
        return static::where('token_hash', hash('sha256', $token))
            ->whereNull('eingeloest_am')
            ->where('gueltig_bis', '>', now())
            ->first();
    }
}

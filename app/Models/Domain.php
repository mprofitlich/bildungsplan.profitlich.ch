<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Eine Maildomain, deren Adressen sich einloggen dürfen.
 *
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Domain extends Model
{
    /** Liefert den Teil einer Adresse nach dem @, klein geschrieben. */
    public static function von(string $email): string
    {
        return mb_strtolower(substr(strrchr($email, '@') ?: '', 1));
    }

    /** Prüft, ob sich eine Adresse einloggen darf; Subdomains zählen nicht mit. */
    public static function erlaubt(string $email): bool
    {
        return static::where('name', static::von($email))->exists();
    }
}

<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Eine Person, die sich mindestens einmal eingeloggt hat.
 *
 * @property int $id
 * @property string $email
 * @property string $domain
 */
#[Fillable(['email', 'domain'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /** @return HasMany<Kurs, $this> */
    public function kurse(): HasMany
    {
        return $this->hasMany(Kurs::class, 'besitzer_id');
    }
}

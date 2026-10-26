<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Erlaubt einer Mailadresse, einen Kurs mitzubearbeiten.
 *
 * @property int $id
 * @property string $kurs_id
 * @property string $email
 */
#[Fillable(['email'])]
class Freigabe extends Model
{
    protected $table = 'freigaben';

    /** @return BelongsTo<Kurs, $this> */
    public function kurs(): BelongsTo
    {
        return $this->belongsTo(Kurs::class);
    }
}

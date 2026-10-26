<?php

namespace App\Policies;

use App\Models\Kurs;
use App\Models\User;

/** Wer einen Kurs bearbeiten und wer ihn verwalten darf (ADR 0002). */
class KursPolicy
{
    /** Titel, Beschrieb und Ziele ändern: Besitzer:in und alle mit Freigabe. */
    public function bearbeiten(User $user, Kurs $kurs): bool
    {
        return $kurs->darfBearbeiten($user);
    }

    /** Löschen, freigeben, im Curriculum zeigen: nur Besitzer:in. */
    public function verwalten(User $user, Kurs $kurs): bool
    {
        return $kurs->istBesitzer($user);
    }
}

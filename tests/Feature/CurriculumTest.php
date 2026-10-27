<?php

namespace Tests\Feature;

use App\Models\Kurs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurriculumTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function zeigt_nur_freigegebene_kurse_der_eigenen_domain(): void
    {
        $sichtbar = Kurs::factory()->create(['titel' => 'Sichtbar', 'kriterien' => ['vermittlung' => 'Probebewerbung']]);
        $sichtbar->forceFill(['im_curriculum' => true])->save();
        Kurs::factory()->create(['titel' => 'Privat']);
        $fremd = Kurs::factory()->for(User::factory()->domain('andere.ch'), 'besitzer')->create(['titel' => 'Fremd']);
        $fremd->forceFill(['im_curriculum' => true])->save();

        $this->actingAs(User::factory()->create())->getJson('/api/curriculum')
            ->assertJson(['domain' => 'sluz.ch'])
            ->assertJsonCount(1, 'kurse')
            ->assertJsonPath('kurse.0.titel', 'Sichtbar')
            ->assertJsonPath('kurse.0.kriterien.vermittlung', 'Probebewerbung')
            ->assertJsonPath('kurse.0.kriterien.entwicklung', '')
            ->assertJsonPath('kurse.0.besitzer', $sichtbar->besitzer->email);
    }
}

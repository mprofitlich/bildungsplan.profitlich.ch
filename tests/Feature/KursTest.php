<?php

namespace Tests\Feature;

use App\Models\Kurs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KursTest extends TestCase
{
    use RefreshDatabase;

    private function daten(array $anders = []): array
    {
        return ['id' => (string) Str::uuid(), 'titel' => 'Typografie', 'beschrieb' => '', 'ziele' => ['a1.1-schule'], ...$anders];
    }

    #[Test]
    public function legt_einen_kurs_mit_der_id_aus_dem_browser_an(): void
    {
        $ich = User::factory()->create();
        $daten = $this->daten();

        $this->actingAs($ich)->postJson('/api/kurse', $daten)
            ->assertCreated()
            ->assertJson(['id' => $daten['id'], 'version' => 1, 'istBesitzer' => true]);

        $this->assertSame($ich->id, Kurs::find($daten['id'])->besitzer_id);
    }

    #[Test]
    public function vergibt_fortlaufende_nummern(): void
    {
        $erster = Kurs::factory()->create();
        $ich = User::factory()->create();

        $this->actingAs($ich)->postJson('/api/kurse', $this->daten())
            ->assertCreated()
            ->assertJson(['nummer' => $erster->nummer + 1]);
    }

    #[Test]
    public function der_import_uebernimmt_lokale_kurse_ohne_duplikate(): void
    {
        $ich = User::factory()->create();
        $kurs = $this->daten();

        $this->actingAs($ich)->postJson('/api/kurse/import', ['kurse' => [$kurs]])->assertJsonCount(1);
        // Ein zweites Gerät bringt denselben Kurs nochmals mit.
        $this->actingAs($ich)->postJson('/api/kurse/import', ['kurse' => [[...$kurs, 'titel' => 'Älter']]])
            ->assertJsonCount(1)
            ->assertJsonPath('0.titel', 'Typografie');
    }

    #[Test]
    public function der_import_uebergeht_den_leeren_anfangskurs(): void
    {
        $ich = User::factory()->create();

        $this->actingAs($ich)
            ->postJson('/api/kurse/import', ['kurse' => [$this->daten(['titel' => 'Neuer Kurs', 'ziele' => []])]])
            ->assertJsonCount(0);
    }

    #[Test]
    public function der_import_gibt_einem_kurs_mit_fremder_id_eine_neue(): void
    {
        $fremd = Kurs::factory()->create();
        $ich = User::factory()->create();

        $this->actingAs($ich)->postJson('/api/kurse/import', ['kurse' => [$this->daten(['id' => $fremd->id])]])
            ->assertJsonCount(1);

        $this->assertSame(2, Kurs::count());
        $this->assertSame($fremd->besitzer_id, $fremd->fresh()->besitzer_id);
    }

    #[Test]
    public function speichert_und_erhoeht_die_version(): void
    {
        $kurs = Kurs::factory()->create();

        $this->actingAs($kurs->besitzer)
            ->putJson("/api/kurse/{$kurs->id}", ['titel' => 'Neu', 'beschrieb' => null, 'ziele' => ['b1.1-betrieb'], 'version' => 1])
            ->assertOk()
            ->assertJson(['titel' => 'Neu', 'beschrieb' => '', 'version' => 2, 'sperre' => ['eigene' => true]]);
    }

    #[Test]
    public function speichert_die_beurteilungskriterien_und_ergaenzt_fehlende(): void
    {
        $kurs = Kurs::factory()->create();
        $daten = ['titel' => 'Neu', 'beschrieb' => '', 'ziele' => [], 'version' => 1];

        $this->actingAs($kurs->besitzer)
            ->putJson("/api/kurse/{$kurs->id}", [...$daten, 'kriterien' => ['vermittlung' => 'Probebewerbung', 'umsetzung' => null]])
            ->assertOk()
            ->assertJson(['kriterien' => ['entwicklung' => '', 'gestaltungskonzept' => '', 'umsetzung' => '', 'vermittlung' => 'Probebewerbung']]);
    }

    #[Test]
    public function behaelt_die_kriterien_wenn_ein_alter_stand_ohne_sie_speichert(): void
    {
        $kurs = Kurs::factory()->create(['kriterien' => ['vermittlung' => 'Probebewerbung']]);

        $this->actingAs($kurs->besitzer)
            ->putJson("/api/kurse/{$kurs->id}", ['titel' => 'Neu', 'beschrieb' => '', 'ziele' => [], 'version' => 1])
            ->assertOk()
            ->assertJsonPath('kriterien.vermittlung', 'Probebewerbung');
    }

    #[Test]
    public function lehnt_unbekannte_kriterien_ab(): void
    {
        $ich = User::factory()->create();

        $this->actingAs($ich)->postJson('/api/kurse', $this->daten(['kriterien' => ['erfunden' => 'x']]))
            ->assertUnprocessable();
    }

    #[Test]
    public function der_import_uebernimmt_einen_kurs_der_nur_kriterien_hat(): void
    {
        $ich = User::factory()->create();
        $kurs = $this->daten(['titel' => 'Neuer Kurs', 'ziele' => [], 'kriterien' => ['entwicklung' => 'Skizzen']]);

        $this->actingAs($ich)->postJson('/api/kurse/import', ['kurse' => [$kurs]])
            ->assertJsonCount(1)
            ->assertJsonPath('0.kriterien.entwicklung', 'Skizzen');
    }

    #[Test]
    public function lehnt_eine_speicherung_auf_veraltetem_stand_ab(): void
    {
        $kurs = Kurs::factory()->create(['titel' => 'Aktuell']);
        $kurs->forceFill(['version' => 3])->save();

        $this->actingAs($kurs->besitzer)
            ->putJson("/api/kurse/{$kurs->id}", ['titel' => 'Alt', 'beschrieb' => '', 'ziele' => [], 'version' => 2])
            ->assertStatus(409)
            ->assertJson(['titel' => 'Aktuell', 'version' => 3]);
    }

    #[Test]
    public function fremde_kurse_lassen_sich_weder_lesen_noch_aendern(): void
    {
        $kurs = Kurs::factory()->create();
        $andere = User::factory()->create();

        $this->actingAs($andere)->getJson('/api/kurse')->assertJsonCount(0);
        $this->actingAs($andere)->getJson("/api/kurse/{$kurs->id}")->assertForbidden();
        $this->actingAs($andere)
            ->putJson("/api/kurse/{$kurs->id}", ['titel' => 'x', 'beschrieb' => '', 'ziele' => [], 'version' => 1])
            ->assertForbidden();
    }

    #[Test]
    public function nur_wer_den_kurs_besitzt_darf_loeschen_und_ins_curriculum_stellen(): void
    {
        $kurs = Kurs::factory()->create();
        $mit = User::factory()->create();
        $kurs->freigaben()->create(['email' => $mit->email]);

        $this->actingAs($mit)->deleteJson("/api/kurse/{$kurs->id}")->assertForbidden();
        $this->actingAs($mit)->putJson("/api/kurse/{$kurs->id}/curriculum", ['sichtbar' => true])->assertForbidden();

        $this->actingAs($kurs->besitzer)->putJson("/api/kurse/{$kurs->id}/curriculum", ['sichtbar' => true])
            ->assertJson(['imCurriculum' => true]);
        $this->actingAs($kurs->besitzer)->deleteJson("/api/kurse/{$kurs->id}")->assertNoContent();
        $this->assertNull(Kurs::find($kurs->id));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Kurs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SperreTest extends TestCase
{
    use RefreshDatabase;

    private Kurs $kurs;

    private User $mit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kurs = Kurs::factory()->create();
        $this->mit = User::factory()->create();
        $this->kurs->freigaben()->create(['email' => $this->mit->email]);
    }

    #[Test]
    public function wer_die_sperre_haelt_wird_den_anderen_gezeigt(): void
    {
        $this->actingAs($this->kurs->besitzer)->postJson("/api/kurse/{$this->kurs->id}/sperre")->assertOk();

        $this->actingAs($this->mit)->getJson("/api/kurse/{$this->kurs->id}")
            ->assertJson(['sperre' => ['email' => $this->kurs->besitzer->email, 'eigene' => false]]);
    }

    #[Test]
    public function solange_jemand_sperrt_nimmt_der_kurs_keine_aenderung_an(): void
    {
        $this->actingAs($this->kurs->besitzer)->postJson("/api/kurse/{$this->kurs->id}/sperre");

        $this->actingAs($this->mit)->postJson("/api/kurse/{$this->kurs->id}/sperre")->assertStatus(423);
        $this->actingAs($this->mit)
            ->putJson("/api/kurse/{$this->kurs->id}", ['titel' => 'x', 'beschrieb' => '', 'ziele' => [], 'version' => 1])
            ->assertStatus(423);
        $this->assertSame(1, $this->kurs->fresh()->version);
    }

    #[Test]
    public function die_sperre_laeuft_ohne_erneuerung_ab(): void
    {
        $this->actingAs($this->kurs->besitzer)->postJson("/api/kurse/{$this->kurs->id}/sperre");

        $this->travel(Kurs::SPERRE_SEKUNDEN + 1)->seconds();

        $this->actingAs($this->mit)->postJson("/api/kurse/{$this->kurs->id}/sperre")
            ->assertOk()
            ->assertJson(['sperre' => ['eigene' => true]]);
    }

    #[Test]
    public function wer_sperrt_kann_die_sperre_freigeben_andere_nicht(): void
    {
        $this->actingAs($this->kurs->besitzer)->postJson("/api/kurse/{$this->kurs->id}/sperre");

        $this->actingAs($this->mit)->deleteJson("/api/kurse/{$this->kurs->id}/sperre")->assertJsonPath('sperre.eigene', false);
        $this->actingAs($this->kurs->besitzer)->deleteJson("/api/kurse/{$this->kurs->id}/sperre")->assertJson(['sperre' => null]);
    }
}

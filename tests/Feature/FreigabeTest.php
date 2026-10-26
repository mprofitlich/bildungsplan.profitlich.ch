<?php

namespace Tests\Feature;

use App\Mail\FreigabeMail;
use App\Models\Kurs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FreigabeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function gibt_an_eine_adresse_frei_und_schickt_eine_mail(): void
    {
        Mail::fake();
        $kurs = Kurs::factory()->create();

        $this->actingAs($kurs->besitzer)->postJson("/api/kurse/{$kurs->id}/freigaben", ['email' => 'Neu@sluz.ch'])
            ->assertOk()
            ->assertJson(['freigaben' => ['neu@sluz.ch']]);
        // Nochmals freigeben ändert nichts und schickt keine zweite Mail.
        $this->actingAs($kurs->besitzer)->postJson("/api/kurse/{$kurs->id}/freigaben", ['email' => 'neu@sluz.ch']);

        Mail::assertSent(FreigabeMail::class, 1);
        Mail::assertSent(FreigabeMail::class, fn ($mail) => $mail->hasTo('neu@sluz.ch'));
    }

    #[Test]
    public function die_freigabe_gilt_auch_fuer_wer_sich_erst_spaeter_einloggt(): void
    {
        Mail::fake();
        $kurs = Kurs::factory()->create();
        $this->actingAs($kurs->besitzer)->postJson("/api/kurse/{$kurs->id}/freigaben", ['email' => 'spaeter@sluz.ch']);

        $spaeter = User::factory()->create(['email' => 'spaeter@sluz.ch']);

        $this->actingAs($spaeter)->getJson('/api/kurse')
            ->assertJsonCount(1)
            ->assertJsonPath('0.istBesitzer', false)
            ->assertJsonPath('0.freigaben', []);
        $this->actingAs($spaeter)
            ->putJson("/api/kurse/{$kurs->id}", ['titel' => 'Gemeinsam', 'beschrieb' => '', 'ziele' => [], 'version' => 1])
            ->assertOk();
    }

    #[Test]
    public function gibt_nur_an_dieselbe_domain_frei(): void
    {
        Mail::fake();
        $kurs = Kurs::factory()->create();

        $this->actingAs($kurs->besitzer)->postJson("/api/kurse/{$kurs->id}/freigaben", ['email' => 'anna@gmail.com'])
            ->assertJsonValidationErrors('email');
        Mail::assertNothingSent();
    }

    #[Test]
    public function nur_wer_den_kurs_besitzt_darf_freigeben(): void
    {
        $kurs = Kurs::factory()->create();
        $mit = User::factory()->create();
        $kurs->freigaben()->create(['email' => $mit->email]);

        $this->actingAs($mit)->postJson("/api/kurse/{$kurs->id}/freigaben", ['email' => 'dritte@sluz.ch'])->assertForbidden();
        $this->actingAs($mit)->deleteJson("/api/kurse/{$kurs->id}/freigaben", ['email' => $mit->email])->assertForbidden();
    }

    #[Test]
    public function zieht_eine_freigabe_zurueck(): void
    {
        $kurs = Kurs::factory()->create();
        $mit = User::factory()->create();
        $kurs->freigaben()->create(['email' => $mit->email]);

        $this->actingAs($kurs->besitzer)->deleteJson("/api/kurse/{$kurs->id}/freigaben", ['email' => $mit->email])
            ->assertJson(['freigaben' => []]);
        $this->actingAs($mit)->getJson('/api/kurse')->assertJsonCount(0);
    }

    #[Test]
    public function schlaegt_nur_personen_der_eigenen_domain_vor(): void
    {
        $ich = User::factory()->create(['email' => 'ich@sluz.ch']);
        User::factory()->create(['email' => 'anna.muster@sluz.ch']);
        User::factory()->create(['email' => 'bruno@sluz.ch']);
        User::factory()->domain('andere.ch')->create(['email' => 'anna@andere.ch']);

        $this->actingAs($ich)->getJson('/api/personen?suche=anna')->assertExactJson(['anna.muster@sluz.ch']);
        $this->actingAs($ich)->getJson('/api/personen')->assertExactJson(['anna.muster@sluz.ch', 'bruno@sluz.ch']);
    }
}

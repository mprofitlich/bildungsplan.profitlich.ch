<?php

namespace Tests\Feature;

use App\Mail\LoginLinkMail;
use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** Fordert einen Link an und liefert das Token aus der Mail. */
    private function linkAnfordern(string $email): string
    {
        Mail::fake();
        $this->postJson('/api/login', ['email' => $email])->assertOk();
        $url = null;
        Mail::assertSent(LoginLinkMail::class, function (LoginLinkMail $mail) use (&$url) {
            $url = $mail->url;

            return true;
        });

        return basename($url);
    }

    #[Test]
    public function schickt_einen_link_an_adressen_einer_erlaubten_domain(): void
    {
        Mail::fake();

        $this->postJson('/api/login', ['email' => ' Anna.Muster@SLUZ.ch '])->assertOk();

        Mail::assertSent(LoginLinkMail::class, fn ($mail) => $mail->hasTo('anna.muster@sluz.ch'));
    }

    #[Test]
    public function lehnt_andere_domains_und_subdomains_ab(): void
    {
        Mail::fake();

        $this->postJson('/api/login', ['email' => 'anna@gmail.com'])->assertJsonValidationErrors('email');
        $this->postJson('/api/login', ['email' => 'anna@schule.sluz.ch'])->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    #[Test]
    public function begrenzt_die_zahl_der_links_je_adresse(): void
    {
        Mail::fake();
        foreach (range(1, 3) as $_) {
            $this->postJson('/api/login', ['email' => 'anna@sluz.ch'])->assertOk();
        }

        $this->postJson('/api/login', ['email' => 'anna@sluz.ch'])->assertJsonValidationErrors('email');
        Mail::assertSentCount(3);
    }

    #[Test]
    public function der_link_zeigt_erst_einen_knopf_und_loggt_nicht_selbst_ein(): void
    {
        $token = $this->linkAnfordern('anna@sluz.ch');

        $this->get("/login/{$token}")->assertOk()->assertSee('Einloggen');

        $this->assertGuest();
        $this->assertNotNull(LoginLink::gueltig($token));
    }

    #[Test]
    public function der_knopf_loggt_ein_und_legt_die_person_an(): void
    {
        $token = $this->linkAnfordern('anna@sluz.ch');

        $this->post("/login/{$token}")->assertRedirect('/');

        $user = User::where('email', 'anna@sluz.ch')->firstOrFail();
        $this->assertSame('sluz.ch', $user->domain);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function ein_link_gilt_nur_einmal(): void
    {
        $token = $this->linkAnfordern('anna@sluz.ch');
        $this->post("/login/{$token}");
        $this->post('/api/logout');

        $this->post("/login/{$token}")->assertOk()->assertSee('abgelaufen');

        $this->assertGuest();
    }

    #[Test]
    public function ein_link_laeuft_nach_einer_viertelstunde_ab(): void
    {
        $token = $this->linkAnfordern('anna@sluz.ch');

        $this->travel(LoginLink::GUELTIG_MINUTEN + 1)->minutes();

        $this->post("/login/{$token}")->assertSee('abgelaufen');
        $this->assertGuest();
    }

    #[Test]
    public function die_seite_nennt_die_eingeloggte_person(): void
    {
        $user = User::factory()->create(['email' => 'anna@sluz.ch']);

        $this->actingAs($user)->get('/')->assertSee('anna@sluz.ch');
    }

    #[Test]
    public function die_api_verlangt_ein_login(): void
    {
        $this->getJson('/api/kurse')->assertUnauthorized();
        $this->getJson('/api/curriculum')->assertUnauthorized();
    }
}

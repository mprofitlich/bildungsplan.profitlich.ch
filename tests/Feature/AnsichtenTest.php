<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnsichtenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function liefert_die_anwendung_unter_den_pfaden_der_ansichten(): void
    {
        $pfade = [
            '/', '/glossar', '/meine-kurse', '/kurs', '/kurs/42', '/kurs/42-typografie-grundlagen',
            '/kurs/3f2a6b1c-0d4e-4f5a-8b9c-1d2e3f4a5b6c', '/kurs/3f2a6b1c-0d4e-4f5a-8b9c-1d2e3f4a5b6c-typografie',
        ];
        foreach ($pfade as $pfad) {
            $this->get($pfad)->assertOk()->assertSee('id="app"', false);
        }
    }

    #[Test]
    public function kennt_keine_anderen_pfade(): void
    {
        $this->get('/irgendwas')->assertNotFound();
        $this->get('/kurs/keine-id')->assertNotFound();
        $this->get('/kurs/42-Gross_und_klein')->assertNotFound();
        $this->get('/kurs/42/typografie')->assertNotFound();
    }
}

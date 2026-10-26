<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Die Seiten binden das Frontend über Vite ein; in Tests gibt es keinen Build.
        $this->withoutVite();
    }
}

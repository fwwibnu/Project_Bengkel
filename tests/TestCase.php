<?php

namespace Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tidak butuh hasil npm run build saat test
        $this->withoutVite();

        // Halaman & URL Filament butuh panel aktif
        Filament::setCurrentPanel(Filament::getPanel('app'));
    }
}

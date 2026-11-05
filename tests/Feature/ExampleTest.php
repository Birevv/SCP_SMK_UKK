<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $panel = Filament::getPanel('admin');
        $path = trim($panel->getPath(), '/');

        if (blank($path)) {
            // Ketika panel berada di root, user diarahkan ke login panel oleh Authenticate middleware
            $response->assertRedirect($panel->getLoginUrl());
        } else {
            // Ketika panel berada di path khusus (misal /admin), root diarahkan ke url panel
            $response->assertRedirect($panel->getUrl());
        }
    }
}

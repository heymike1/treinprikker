<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_pages_show_the_treinprikker_404(): void
    {
        $this->get('/deze-pagina-bestaat-niet')
            ->assertNotFound()
            ->assertSee('Deze bestemming staat niet op de kaart')
            ->assertSee(route('home'));
    }

    public function test_forbidden_pages_show_the_treinprikker_403(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('Dit perron is niet toegankelijk');
    }

    public function test_the_other_error_pages_render(): void
    {
        $this->view('errors.500')->assertSee('Storing op het spoor')->assertSee('Opnieuw proberen');
        $this->view('errors.503')->assertSee('Even geen treinen');
        $this->view('errors.419')->assertSee('Je kaartje is verlopen');
    }
}

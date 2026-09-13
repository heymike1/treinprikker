<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    public function test_unknown_pages_show_the_treinprikker_404(): void
    {
        $this->get('/deze-pagina-bestaat-niet')
            ->assertNotFound()
            ->assertSee('Dit station bestaat niet')
            ->assertSee(route('home'));
    }
}

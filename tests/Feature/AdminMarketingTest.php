<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMarketingTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_page_renders_without_data(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/marketing')
            ->assertOk()
            ->assertSee('Carousel: zo werkt het')
            ->assertSee('Nog te weinig data deze week');
    }
}

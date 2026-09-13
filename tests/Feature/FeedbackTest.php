<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_page_renders(): void
    {
        $this->get('/feedback')->assertOk()->assertSee('Soort feedback');
    }

    public function test_feedback_is_stored(): void
    {
        $this->post('/feedback', ['name' => 'Mike', 'category' => 'idee', 'message' => 'Maak een oefenmodus alsjeblieft.'])
            ->assertRedirect(route('feedback.create'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('feedback', ['name' => 'Mike', 'category' => 'idee']);
    }

    public function test_feedback_requires_a_valid_category_and_message(): void
    {
        $this->post('/feedback', ['category' => 'spam', 'message' => 'kort'])
            ->assertSessionHasErrors(['category', 'message']);

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_honeypot_silently_drops_bots(): void
    {
        $this->post('/feedback', ['website' => 'http://spam', 'category' => 'idee', 'message' => 'Buy cheap things now!!'])
            ->assertRedirect();

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_admin_sees_feedback_and_can_mark_it_read(): void
    {
        $item = Feedback::create(['category' => 'bug', 'message' => 'De kaart laadt niet op mijn telefoon.']);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/feedback')->assertOk()->assertSee('De kaart laadt niet');
        $this->actingAs($admin)->post('/admin/feedback/'.$item->id.'/gelezen')->assertRedirect();
        $this->assertNotNull($item->fresh()->read_at);
    }
}

<?php

namespace Tests\Feature;

use App\Game\DailyGameGenerator;
use App\Models\DailyGame;
use App\Models\GameSession;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_requires_authentication_and_admin_flag(): void
    {
        $this->get('/admin')->assertStatus(401);
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get('/admin')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_admin_pages_render(): void
    {
        Station::factory()->count(9)->create();
        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/dagen')->assertOk()->assertSee('#'.$game->game_number);
        $this->actingAs($admin)->get('/admin/dagen/'.$game->id)->assertOk();
        $this->actingAs($admin)->get('/admin/stations')->assertOk();
        $this->actingAs($admin)->get('/admin/stations/'.Station::first()->slug)->assertOk();
        $this->actingAs($admin)->get('/admin/statistieken')->assertOk();
    }

    public function test_admin_cannot_regenerate_a_played_game_without_force(): void
    {
        Station::factory()->count(9)->create();
        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());
        GameSession::factory()->create(['daily_game_id' => $game->id]);
        $before = $game->stations->pluck('station_id')->all();

        $this->actingAs($this->admin())->post('/admin/dagen/'.$game->id.'/regenereer')->assertSessionHas('error');
        $this->assertSame($before, $game->fresh()->stations->pluck('station_id')->all());

        $this->actingAs($this->admin())->post('/admin/dagen/'.$game->id.'/regenereer', ['force' => 1])->assertSessionHas('status');
        $this->assertSame(0, GameSession::count());
    }

    public function test_admin_can_edit_and_toggle_a_station(): void
    {
        $station = Station::factory()->create();

        $this->actingAs($this->admin())->put('/admin/stations/'.$station->slug, [
            'name' => 'Nieuwe Naam', 'slug' => 'nieuwe-naam', 'code' => 'NN', 'uic' => '8400999',
            'latitude' => 52.123456, 'longitude' => 5.123456, 'province' => 'Utrecht', 'municipality' => 'Test',
            'station_type' => 'stoptreinstation', 'difficulty_rating' => 42, 'active' => 1,
        ])->assertRedirect();

        $station->refresh();
        $this->assertSame('Nieuwe Naam', $station->name);
        $this->assertEqualsWithDelta(52.123456, $station->latitude, 0.000001);
        $this->assertSame(42, $station->difficulty_rating);

        $this->actingAs($this->admin())->post('/admin/stations/'.$station->slug.'/toggle')->assertRedirect();
        $this->assertFalse($station->fresh()->active);
    }
}

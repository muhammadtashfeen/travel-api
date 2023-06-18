<?php

namespace Tests\Feature;

use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelsListTest extends TestCase
{
    use RefreshDatabase;

    public function testTravelListReturnsPaginatedData(): void
    {
        Travel::factory(16)->create([
            'is_public' => true,
        ]);

        $response = $this->get('/api/v1/travels');

        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function testTravelListOnlyShowsPublicRecords()
    {
        $travel = Travel::factory()->create(['is_public' => true]);
        Travel::factory()->create(['is_public' => false]);

        $response = $this->get('/api/v1/travels');

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', $travel->name);
    }
}

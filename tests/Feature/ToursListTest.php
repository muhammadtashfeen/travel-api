<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToursListTest extends TestCase
{
    use RefreshDatabase;

    public function testTourExistUnderCorrectSlug()
    {
        $travel = Travel::factory()->create();
        $tour = Tour::factory()->create(['travel_id' => $travel->id]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $tour->id]);
    }

    public function testPriceIsShowCorrectly()
    {
        $travel = Travel::factory()->create();
        $tour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 123.45,
        ]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'id' => $tour->id,
            'price' => '123.45',
        ]);
    }

    public function testTourListsArePaginated()
    {
        $travel = Travel::factory()->create();
        $tours = Tour::factory(16)->create(['travel_id' => $travel->id]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function testTourListSortsByStartingDateCorrectly()
    {
        $travel = Travel::factory()->create();

        $laterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);

        $earlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now(),
            'ending_date' => now()->addDays(1),
        ]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $earlierTour->id);
        $response->assertJsonPath('data.1.id', $laterTour->id);
    }

    public function testTourListSortsByPriceCorrectly()
    {
        $travel = Travel::factory()->create();

        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 200,
        ]);
        $cheapLaterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);
        $cheapEarlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'starting_date' => now(),
            'ending_date' => now()->addDays(1),
        ]);

        $response = $this->get("/api/v1/travels/{$travel->slug}/tours?sortBy=price&sortOrder=asc");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $cheapEarlierTour->id);
        $response->assertJsonPath('data.1.id', $cheapLaterTour->id);
        $response->assertJsonPath('data.2.id', $expensiveTour->id);
    }

    public function testTourListFiltersByPriceCorrectly()
    {
        $travel = Travel::factory()->create();

        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 200,
        ]);
        $cheapTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
        ]);

        $endpoint = "/api/v1/travels/{$travel->slug}/tours";

        $response = $this->get("$endpoint?priceFrom=100");
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get("$endpoint?priceFrom=150");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $expensiveTour->id]);
        $response->assertJsonMissing(['id' => $cheapTour->id]);

        $response = $this->get("$endpoint?priceFrom=250");
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        $response = $this->get("$endpoint?priceTo=150");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
    }

    public function testTourListReturnsValidationErrors()
    {
        $travel = Travel::factory()->create();

        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 200,
        ]);
        $cheapTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
        ]);

        $endpoint = "/api/v1/travels/{$travel->slug}/tours";

        $response = $this->getJson("$endpoint?dateFrom=abc");
        $response->assertStatus(422);

        $response = $this->getJson("$endpoint?sortBy=abcde");
        $response->assertStatus(422);
    }
}

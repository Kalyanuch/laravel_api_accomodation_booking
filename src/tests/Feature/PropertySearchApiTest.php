<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PropertySearchApiTest extends TestCase
{
    use DatabaseMigrations;

    private Supplier $supplier;

    private Import $import;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::factory()->create();
        $this->import = Import::factory()->create(['supplier_id' => $this->supplier]);
    }

    public function test_it_returns_the_cheapest_current_offer_for_each_matching_property(): void
    {
        $barcelona = Property::factory()->create(['city' => 'Barcelona']);
        $madrid = Property::factory()->create(['city' => 'Madrid']);

        $expensive = $this->offer($barcelona, ['price' => 30000]);
        $cheapest = $this->offer($barcelona, ['price' => 20000]);
        $this->offer($madrid, ['price' => 10000]);

        $response = $this->getJson($this->searchUrl(['city' => 'Barcelona']));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $barcelona->code)
            ->assertJsonPath('data.0.best_offer.id', $cheapest->getKey())
            ->assertJsonPath('data.0.best_offer.price', 20000)
            ->assertJsonPath('data.0.best_offer.supplier', $this->supplier->code)
            ->assertJsonPath('next', null)
            ->assertJsonPath('prev', null)
            ->assertJsonPath('per_page', 10);

        $this->assertNotSame($expensive->getKey(), $response->json('data.0.best_offer.id'));
    }

    public function test_it_excludes_offers_that_are_not_current_or_do_not_satisfy_the_search(): void
    {
        $matching = Property::factory()->create();
        $wrongDates = Property::factory()->create();
        $tooSmall = Property::factory()->create();
        $soldOut = Property::factory()->create();
        $expired = Property::factory()->create();

        $this->offer($matching);
        $this->offer($wrongDates, ['check_in' => '2027-02-01', 'check_out' => '2027-02-05']);
        $this->offer($tooSmall, ['max_guests' => 1]);
        $this->offer($soldOut, ['available_units' => 0]);
        $this->offer($expired, ['expires_at' => now()->subMinute()]);

        $this->getJson($this->searchUrl())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $matching->code);
    }

    public function test_equal_prices_use_the_lowest_offer_id_as_a_stable_tie_breaker(): void
    {
        $property = Property::factory()->create();
        $first = $this->offer($property, ['price' => 15000]);
        $this->offer($property, ['price' => 15000]);

        $this->getJson($this->searchUrl())
            ->assertOk()
            ->assertJsonPath('data.0.best_offer.id', $first->getKey());
    }

    public function test_it_sorts_and_paginates_results_in_the_database(): void
    {
        foreach (range(1, 11) as $number) {
            $property = Property::factory()->create();
            $this->offer($property, ['price' => $number * 1000]);
        }

        $firstPage = $this->getJson($this->searchUrl());
        $secondPage = $this->getJson($this->searchUrl(['page' => 2]));

        $firstPage
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.best_offer.price', 1000)
            ->assertJsonPath('data.9.best_offer.price', 10000)
            ->assertJsonPath('prev', null)
            ->assertJsonPath('per_page', 10);
        $this->assertNotNull($firstPage->json('next'));

        $secondPage
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.best_offer.price', 11000)
            ->assertJsonPath('next', null);
        $this->assertNotNull($secondPage->json('prev'));
    }

    public function test_it_validates_search_parameters(): void
    {
        $this->getJson('/api/properties?check_in=bad&check_out=2026-10-01&guests=0&page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_in', 'check_out', 'guests', 'page']);
    }

    /** @param array<string, mixed> $overrides */
    private function offer(Property $property, array $overrides = []): Offer
    {
        return Offer::factory()->create(array_merge([
            'supplier_id' => $this->supplier,
            'property_id' => $property,
            'import_id' => $this->import,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'available_units' => 2,
            'expires_at' => now()->addMonth(),
            'currency' => 'EUR',
        ], $overrides));
    }

    /** @param array<string, int|string> $overrides */
    private function searchUrl(array $overrides = []): string
    {
        return '/api/properties?'.http_build_query(array_merge([
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ], $overrides));
    }
}

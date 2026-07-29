<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\FeedRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_normalizes_magento_prices_without_currency_codes(): void
    {
        $user = User::factory()->create();

        $feed = Feed::create([
            'user_id' => $user->id,
            'name' => 'Magento Feed',
            'original_filename' => 'magento.csv',
            'storage_path' => 'feeds/magento.csv',
            'mime_type' => 'text/csv',
            'status' => 'done',
            'row_count' => 1,
        ]);

        FeedRow::create([
            'feed_id' => $feed->id,
            'row_number' => 1,
            'data' => [
                'sku' => '24-WG085',
                'attribute_set_code' => 'Default',
                'product_type' => 'simple',
                'product_websites' => 'base',
                'name' => 'Sprite Yoga Strap 6 foot',
                'price' => '14.00 USD',
            ],
            'status' => 'valid',
            'issues' => [],
        ]);

        $response = $this->actingAs($user)->get(route('feeds.export', $feed));

        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('price', $csv);
        $this->assertStringContainsString('14.00', $csv);
        $this->assertStringNotContainsString('14.00 USD', $csv);
    }
}

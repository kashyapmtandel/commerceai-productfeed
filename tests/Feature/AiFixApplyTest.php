<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\FeedRow;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFixApplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_ai_fix_resolves_missing_identifier_warning_with_mpn_fallback(): void
    {
        $user = User::factory()->create();

        $feed = Feed::create([
            'user_id' => $user->id,
            'name' => 'Sample Test Feed',
            'original_filename' => 'sample.csv',
            'storage_path' => 'feeds/sample.csv',
            'mime_type' => 'text/csv',
            'status' => 'done',
            'row_count' => 1,
            'warning_count' => 1,
        ]);

        $row = FeedRow::create([
            'feed_id' => $feed->id,
            'row_number' => 5,
            'data' => [
                'id' => 'SKU 005',
                'title' => "Levi's 501 Original Fit Jeans",
                'description' => "Classic straight leg jeans made from 100% cotton denim with iconic Levi's button fly.",
                'link' => 'https://example.com/products/levis-501',
                'image_link' => 'http://example.com/images/levis-501.png',
                'availability' => 'preorder',
                'price' => '89.99',
                'brand' => 'Levis',
                'gtin' => '',
                'condition' => 'used',
                'google_product_category' => 'Apparel & Accessories > Clothing > Pants',
            ],
            'status' => 'warning',
            'issues' => [
                ['field' => 'gtin', 'type' => 'warning', 'message' => 'Missing both GTIN and MPN.'],
            ],
            'ai_fixed_data' => [
                'id' => 'SKU-005',
                'title' => "Levi's 501 Original Fit Jeans",
                'description' => "Classic straight leg jeans made from 100% cotton denim with iconic Levi's button fly.",
                'link' => 'https://example.com/products/levis-501',
                'image_link' => 'https://example.com/images/levis-501.png',
                'availability' => 'preorder',
                'price' => '89.99 USD',
                'brand' => 'Levis',
                'gtin' => '',
                'condition' => 'used',
                'google_product_category' => 'Apparel & Accessories > Clothing > Pants',
            ],
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('feeds.rows.ai-apply', [$feed, $row]));

        $response->assertOk()
            ->assertJson([
                'new_status' => 'valid',
                'issues' => [],
                'issue_count' => 0,
                'ai_applied' => true,
            ])
            ->assertJsonPath('fixed_data.mpn', 'SKU-005');

        $row->refresh();
        $feed->refresh();

        $this->assertSame('valid', $row->status);
        $this->assertSame([], $row->issues);
        $this->assertTrue($row->ai_applied);
        $this->assertSame('SKU-005', $row->fixed_data['mpn']);
        $this->assertSame(0, $feed->warning_count);
    }

    public function test_suggest_ai_fix_returns_retryable_error_when_gemini_is_temporarily_unavailable(): void
    {
        $user = User::factory()->create();

        $feed = Feed::create([
            'user_id' => $user->id,
            'name' => 'Sample Test Feed',
            'original_filename' => 'sample.csv',
            'storage_path' => 'feeds/sample.csv',
            'mime_type' => 'text/csv',
            'status' => 'done',
            'row_count' => 1,
            'error_count' => 1,
        ]);

        $row = FeedRow::create([
            'feed_id' => $feed->id,
            'row_number' => 6,
            'data' => [
                'id' => 'SKU-006',
                'title' => 'Instant Pot Duo 7-in-1 Electric Pressure Cooker 6 Quart',
                'description' => 'Electric pressure cooker.',
                'link' => 'slow cooker',
                'image_link' => 'rice cooker',
                'availability' => 'steamer',
                'price' => 'saute pan',
                'brand' => 'Instant Pot',
            ],
            'status' => 'error',
            'issues' => [
                ['field' => 'link', 'type' => 'error', 'message' => 'Link is not a valid URL.'],
            ],
        ]);

        $this->mock(GeminiService::class, function ($mock) use ($row) {
            $mock->shouldReceive('suggestFix')
                ->once()
                ->with($row->data, $row->issues)
                ->andReturn([
                    'suggestion' => null,
                    'fixed_data' => null,
                    'error' => 'Gemini is experiencing high demand. Waiting to retry.',
                    'retryable' => true,
                    'retry_after' => 15,
                    'status' => 503,
                ]);
        });

        $response = $this->actingAs($user)
            ->postJson(route('feeds.rows.ai-suggest', [$feed, $row]));

        $response->assertStatus(503)
            ->assertJson([
                'error' => 'Gemini is experiencing high demand. Waiting to retry.',
                'retryable' => true,
                'retry_after' => 15,
            ]);
    }
}

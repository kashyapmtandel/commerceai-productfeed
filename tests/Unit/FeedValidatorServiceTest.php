<?php

namespace Tests\Unit;

use App\Services\FeedValidatorService;
use PHPUnit\Framework\TestCase;

class FeedValidatorServiceTest extends TestCase
{
    public function test_magento_price_does_not_require_currency_code(): void
    {
        $validator = new FeedValidatorService();

        $result = $validator->validate([
            'sku' => '24-WG085',
            'attribute_set_code' => 'Default',
            'product_type' => 'simple',
            'product_websites' => 'base',
            'id' => '24-WG085',
            'title' => 'Sprite Yoga Strap 6 foot',
            'description' => 'Durable cotton yoga strap for stretching and alignment.',
            'link' => 'https://example.com/sprite-yoga-strap-6-foot.html',
            'image_link' => 'https://example.com/images/24-WG085.jpg',
            'availability' => 'in stock',
            'price' => '14.00',
            'brand' => 'Sprite',
            'mpn' => '24-WG085',
            'google_product_category' => 'Sporting Goods > Exercise & Fitness > Yoga & Pilates > Yoga Straps',
            'condition' => 'new',
        ], 1);

        $this->assertSame('valid', $result['status']);
        $this->assertSame([], $result['issues']);
    }
}

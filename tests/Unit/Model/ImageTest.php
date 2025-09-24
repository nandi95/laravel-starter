<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Image::class)]
class ImageTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_it_returns_source_attribute_as_expected(): void
    {
        // Mock the Storage facade
        Storage::fake('s3');

        $image = new Image(['storage_location' => 'https://loremflickr.com/320/240']);
        $this->assertSame('https://loremflickr.com/320/240', $image->source);

        $image->setAttribute('storage_location', 'gallery/1/images/image.jpg');
        // Mock the URL generation
        Storage::shouldReceive('url')
            ->once()
            ->with('gallery/1/images/image.jpg')
            ->andReturn('http://fake-url.com/gallery/1/images/image.jpg');

        $this->assertSame('http://fake-url.com/gallery/1/images/image.jpg', $image->source);
    }
}

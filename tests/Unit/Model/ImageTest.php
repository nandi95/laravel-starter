<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function testItReturnsSourceAttributeAsExpected(): void
    {
        $image = new Image(['storage_location' => 'https://loremflickr.com/320/240']);

        $this->assertSame('https://loremflickr.com/320/240', $image->source);
        $image->setAttribute('storage_location', 'gallery/1/images/image.jpg');
        $this->assertSame(Storage::url('gallery/1/images/image.jpg'), $image->source);
    }
}

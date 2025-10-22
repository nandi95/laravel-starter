<?php

declare(strict_types=1);

use App\Models\Image;
use Illuminate\Support\Facades\Storage;

test('it returns source attribute as expected', function (): void {
    // Mock the Storage facade
    Storage::fake('s3');

    $image = new Image(['storage_location' => 'https://loremflickr.com/320/240']);
    expect($image->source)->toBe('https://loremflickr.com/320/240');

    $image->setAttribute('storage_location', 'gallery/1/images/image.jpg');

    // Mock the URL generation
    Storage::shouldReceive('url')
        ->once()
        ->with('gallery/1/images/image.jpg')
        ->andReturn('http://fake-url.com/gallery/1/images/image.jpg');

    expect($image->source)->toBe('http://fake-url.com/gallery/1/images/image.jpg');
});

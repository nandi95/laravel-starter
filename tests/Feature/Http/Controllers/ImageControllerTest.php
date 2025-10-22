<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\DeleteFile;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->setupRoles();
    $this->user->assignRole(Role::Admin);
    Storage::fake('s3');
    Bus::fake();
});
test('can list images', function (): void {
    Image::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->getJson(route('images.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'width',
                    'height',
                    'title',
                    'source',
                    'usedTimes'
                ]
            ],
            'meta',
            'links'
        ]);
});
test('can store new image', function (): void {
    $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');
    $file->store('tmp');

    Storage::shouldReceive('exists')
        ->andReturn(true);
    Storage::shouldReceive('url')
        ->andReturn('http://example.com/tmp/' . $file->name);
    Storage::shouldReceive('mimeType')
        ->andReturn('image/jpeg');

    $this->actingAs($this->user)
        ->postJson(route('images.store'), [
            'key' => 'tmp/' . $file->name,
            'title' => 'Test Image',
            'size' => $file->getSize(),
            'width' => 800,
            'height' => 600,
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'width',
                'height',
                'title',
                'source'
            ]
        ]);

    $this->assertDatabaseHas('images', [
        'title' => 'Test Image',
        'mime_type' => 'image/jpeg',
    ]);
});
test('can update image title', function (): void {
    $image = Image::factory()->create();

    $this->actingAs($this->user)
        ->putJson(route('images.update', $image), [
            'title' => 'Updated Title'
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'width',
                'height',
                'title',
                'source'
            ]
        ])
        ->assertJson([
            'data' => [
                'title' => 'Updated Title'
            ]
        ]);

    $this->assertDatabaseHas('images', [
        'id' => $image->getKey(),
        'title' => 'Updated Title'
    ]);
});
test('cannot delete image that is in use', function (): void {
    $this->markTestIncomplete('The imageables morph needs to be updated with real values');
    $image = Image::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson(route('images.destroy', $image))
        ->assertForbidden();

    $this->assertDatabaseHas('images', [
        'id' => $image->getKey()
    ]);

    Bus::assertNotDispatched(DeleteFile::class);
});
test('can delete unused image', function (): void {
    $this->markTestIncomplete('The imageables morph needs to be updated with real values');
    $image = Image::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson(route('images.destroy', $image))
        ->assertNoContent();

    $this->assertDatabaseMissing('images', [
        'id' => $image->getKey()
    ]);

    Bus::assertDispatched(DeleteFile::class, static fn ($job): bool => $job->path === $image->storage_location);
});
test('validates image store request', function (): void {
    $this->actingAs($this->user)
        ->postJson(route('images.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['size']);
});
test('validates image update request', function (): void {
    $image = Image::factory()->create();

    $this->actingAs($this->user)
        ->putJson(route('images.update', $image), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

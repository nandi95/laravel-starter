<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Role;
use App\Jobs\DeleteFile;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageControllerTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->setupRoles();
        $this->user->assignRole(Role::Admin);
        Storage::fake('s3');
        Bus::fake();
    }

    public function test_can_list_images(): void
    {
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
    }

    public function test_can_store_new_image(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');
        $path = $file->store('tmp');

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
    }

    public function test_can_update_image_title(): void
    {
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
    }

    public function test_cannot_delete_image_that_is_in_use(): void
    {
        $this->markTestIncomplete('The imageables morph needs to be updated with real values');
        $image = Image::factory()->create();

        $this->actingAs($this->user)
            ->deleteJson(route('images.destroy', $image))
            ->assertForbidden();

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey()
        ]);

        Bus::assertNotDispatched(DeleteFile::class);
    }

    public function test_can_delete_unused_image(): void
    {
        $this->markTestIncomplete('The imageables morph needs to be updated with real values');
        $image = Image::factory()->create();

        $this->actingAs($this->user)
            ->deleteJson(route('images.destroy', $image))
            ->assertNoContent();

        $this->assertDatabaseMissing('images', [
            'id' => $image->getKey()
        ]);

        Bus::assertDispatched(DeleteFile::class, static function ($job) use ($image) {
            return $job->path === $image->storage_location;
        });
    }

    public function test_validates_image_store_request(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('images.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);
    }

    public function test_validates_image_update_request(): void
    {
        $image = Image::factory()->create();

        $this->actingAs($this->user)
            ->putJson(route('images.update', $image), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }
}

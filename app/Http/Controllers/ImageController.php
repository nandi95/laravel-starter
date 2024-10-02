<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AssetType;
use App\Http\Requests\ImageStoreRequest;
use App\Http\Resources\ImageResource;
use App\Jobs\DeleteFile;
use App\Jobs\MoveFile;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Display a listing of the images.
     */
    public function index(): AnonymousResourceCollection
    {
        return ImageResource::collection(Image::withRelatedCount()->paginate());
    }

    /**
     * Update the image in storage.
     */
    public function update(Request $request, Image $image)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255']
        ]);
        $image->update($validated);

        return ImageResource::make($image);
    }

    /**
     * Save an image in storage.
     */
    public function store(ImageStoreRequest $request): ImageResource
    {
        $image = Image::create([
            ...$request->safe()->only(['size', 'width', 'height']),
            'title' => $request->get('title', $request->string('key')->after('tmp/')),
            'mime_type' => Storage::mimeType($request->validated('key')),
            'storage_location' => $request->validated('key')
        ]);

        $newPath = $request->getNewPath(AssetType::Image);

        Bus::chain([
            new MoveFile($request->validated('key'), $newPath),
            fn () => Image::withoutTimestamps(static fn () => $image->update(['storage_location' => $newPath]))
        ])->dispatch();

        return ImageResource::make($image);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Image $image): JsonResponse
    {
        abort_if($image->imageables()->exists(), 403, 'Cannot delete image that is currently used.');

        DeleteFile::dispatch($image->storage_location);
        $image->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\InteractsWithS3;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * @template Part of array{Size: int, PartNumber: int, ETag: string}
 */
class UploadController extends Controller
{
    use InteractsWithS3;

    /**
     * Creates a multipart upload then returns the data for it.
     *
     * @throws Exception
     */
    public function createUpload(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string',
            'type' => 'required|string'
        ]);

        try {
            // destination /tmp has a Lifecycle rule of 1-day expiry
            $result = $this->storageClient()->createMultipartUpload([
                'Bucket' => $this->getConfig('bucket'),
                // todo - these objects are public, so let's not have them as somewhat guessable human names?
                'Key' => 'tmp/' . $request->input('filename'),
                // the browser will download the file with this name
                'ContentDisposition' => 'attachment; filename="' . $request->input('filename') . '"',
                'ContentType' => $request->input('type')
            ]);
        } catch (Throwable $exception) {
            Log::error(
                $exception->getMessage(),
                [
                    'line' => $exception->getLine(),
                    'file' => $exception->getFile(),
                    'code' => $exception->getCode(),
                    'inputFileName' => $request->input('filename'),
                    'inputType' => $request->input('type')
                ]
            );
            throw new RuntimeException(
                'There was an error while trying to create the upload.',
                (int) $exception->getCode(),
                $exception
            );
        }

        return response()->json([
            'key' => $result['Key'],
            'uploadId' => $result['UploadId']
        ], Response::HTTP_CREATED);
    }

    /**
     * Get all the currently uploaded parts for the multipart upload.
     */
    public function getUploadedParts(Request $request, string $uploadId): JsonResponse
    {
        $request->validate(['key' => ['required', 'string']]);

        $parts = $this->listPartsPage($request->input('key'), $uploadId, 0);

        return response()->json($parts->toArray());
    }

    /**
     * Get all the pre-signed urls for the parts of the upload.
     */
    public function signPart(Request $request, string $uploadId, int $partNumber): JsonResponse
    {
        $request->validate(['key' => ['required', 'string']]);

        $command = $this->storageClient()->getCommand('uploadPart', [
            'Bucket' => $this->getConfig('bucket'),
            'Key' => $request->input('key'),
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
            'Body' => '',
        ]);

        return response()->json([
            'url' => (string) $this->storageClient()->createPresignedRequest($command, '+1 hour')->getUri(),
            // one hour in seconds
            'expires' => 3600,
            'method' => 'PUT'
        ]);
    }

    /**
     * @param  Collection<int, Part>  $parts
     * @return Collection<int, Part>
     */
    private function listPartsPage(string $key, string $uploadId, int $partIndex, Collection $parts = new Collection): Collection
    {
        $results = $this->storageClient()->listParts([
            'Bucket' => $this->getConfig('bucket'),
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumberMarker' => $partIndex,
        ]);

        if ($results['Parts']) {
            $parts = $parts->push(...$results['Parts']);

            if ($results['IsTruncated']) {
                $results = $this->listPartsPage($key, $uploadId, $results['NextPartNumberMarker'], $parts);
                /** @var array<int, Part> $listPart */
                $listPart = $results['Parts'];
                $parts = $parts->concat($listPart);
            }
        }

        return $parts;
    }

    /**
     * Completes the multipart upload.
     */
    public function completeUpload(Request $request, string $uploadId): JsonResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
            'parts' => ['required', 'array'],
            'parts.*.PartNumber' => ['required', 'integer'],
            'parts.*.ETag' => ['required', 'string'],
        ]);

        $result = $this->storageClient()->completeMultipartUpload([
            'Bucket' => $this->getConfig('bucket'),
            'Key' => $request->input('key'),
            'UploadId' => $uploadId,
            'MultipartUpload' => ['Parts' => $request->input('parts')],
        ]);

        return response()->json([
            'key' => $result['Key']
        ]);
    }

    /**
     * Aborts the multipart upload.
     */
    public function cancelUpload(Request $request, string $uploadId): JsonResponse
    {
        $request->validate(['key' => ['required', 'string']]);

        $this->storageClient()->abortMultipartUpload([
            'Bucket' => $this->getConfig('bucket'),
            'Key' => $request->input('key'),
            'UploadId' => $uploadId,
        ]);

        return response()->json();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AssetType;
use App\Http\Requests\ImageStoreRequest;
use App\Http\Resources\UserResource;
use App\Jobs\DeleteFile;
use App\Jobs\MoveFile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get authenticated user details
     */
    public function index(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return UserResource::make(
            $user
                ->loadCount('unreadNotifications')
                ->loadMissing(['roles', 'userProviders'])
        );
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', Rule::unique(User::class, 'email')->ignore($user)]
        ]);

        $email = $user->email;

        $user->update($request->only(['name', 'email']));

        if ($email !== $request->get('email')) {
            $user->email_verified_at = null;
            $user->save(['timestamps' => false]);
            $user->sendEmailVerificationNotification();
        }

        return response()->json();
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(ImageStoreRequest $request): JsonResponse
    {
        $request->validateResolved();

        if ($oldPath = $request->user()->avatar) {
            DeleteFile::dispatch($oldPath);
        }

        $newPath = $request->getNewPath(AssetType::Image, $request->user());

        MoveFile::dispatchSync($request->validated('key'), $newPath);

        $request->user()->update(['avatar' => $newPath]);

        return response()->json(['data' => Storage::url($newPath)]);
    }
}

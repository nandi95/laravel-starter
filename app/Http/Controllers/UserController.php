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
use Illuminate\Support\Facades\Log;
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

        Log::debug('Fetching user profile', [
            'user_id' => $user->getKey(),
            'with_counts' => ['unreadNotifications'],
            'with_relations' => ['roles', 'userProviders']
        ]);

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

        Log::info('Updating user profile', [
            'user_id' => $user->getKey(),
            'fields' => array_keys($request->only(['first_name', 'last_name', 'email']))
        ]);

        $request->validate([
            'first_name' => ['required', 'string', 'min:1', 'max:100'],
            'last_name' => ['required', 'string', 'min:1', 'max:100'],
            'email' => ['required', 'email', Rule::unique(User::class, 'email')->ignore($user), 'indisposable']
        ]);

        $email = $user->email;
        $attributes = $request->only(['first_name', 'last_name', 'email']);

        if ($email !== $request->get('email')) {
            $attributes['email_verified_at'] = null;
        }

        $user->update($attributes);

        if ($email !== $request->get('email')) {
            $user->sendEmailVerificationNotification();
            Log::info('User email changed, verification email sent', [
                'user_id' => $user->getKey(),
                'old_email' => $email,
                'new_email' => $request->get('email')
            ]);
        }

        return response()->json();
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(ImageStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        Log::info('Updating user avatar', [
            'user_id' => $user->getKey()
        ]);

        $request->validateResolved();
        $oldPath = $user->avatar;

        if ($oldPath) {
            DeleteFile::dispatch($oldPath);
            Log::debug('Scheduled old avatar deletion', [
                'user_id' => $user->getKey(),
                'old_path' => $oldPath
            ]);
        }

        $newPath = $request->getNewPath(AssetType::Image, $user);
        Log::debug('Moving new avatar file', [
            'user_id' => $user->getKey(),
            'from_key' => $request->validated('key'),
            'to_path' => $newPath
        ]);

        MoveFile::dispatchSync($request->validated('key'), $newPath);

        $user->update(['avatar' => $newPath]);

        return response()->json(['data' => Storage::url($newPath)]);
    }
}

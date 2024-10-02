<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = auth()->user();

        return NotificationResource::collection(
            $user->notifications()
                ->latest()
                ->paginate($request->integer('per_page', 10))
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): NotificationResource
    {
        abort_if($notification->notifiable_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $notification->markAsRead();

        return NotificationResource::make($notification);
    }

    public function markAsUnread(Request $request, DatabaseNotification $notification): NotificationResource
    {
        abort_if($notification->notifiable_id !== auth()->id(), Response::HTTP_FORBIDDEN);

        $notification->markAsUnread();

        return NotificationResource::make($notification);
    }
}

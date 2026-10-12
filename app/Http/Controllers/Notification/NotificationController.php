<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notification;

use App\Actions\Notification\GetNotifications;
use App\Actions\Notification\MarkAllNotificationsRead;
use App\Actions\Notification\MarkNotificationRead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Http\Resources\Notification\NotificationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    public function index(IndexNotificationRequest $request, GetNotifications $getNotifications): Response
    {
        $status = $request->string('status', 'all')->toString();

        return Inertia::render('notifications/Index', [
            'notifications' => $getNotifications->handle($request->user(), $status)
                ->through(fn (DatabaseNotification $notification): array => new NotificationResource($notification)->resolve($request)),
            'status' => $status,
        ]);
    }

    public function update(Request $request, string $notification, MarkNotificationRead $markRead): RedirectResponse
    {
        $markRead->handle($request->user(), $notification);

        return back();
    }

    public function readAll(Request $request, MarkAllNotificationsRead $markAllRead): RedirectResponse
    {
        $markAllRead->handle($request->user());

        return back();
    }
}

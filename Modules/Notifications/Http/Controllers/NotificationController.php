<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()
            ->paginate(20)
            ->through(fn (DatabaseNotification $n) => self::toItem($n));

        // Not 'notifications': that name is the shared prop for the header bell.
        return Inertia::render('Notifications::Index', ['items' => $notifications]);
    }

    /** Mark as read, then open its link. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url ? redirect()->to($url) : to_route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public static function toItem(DatabaseNotification $n): array
    {
        return [
            'id' => $n->id,
            'title' => $n->data['title'] ?? '',
            'body' => $n->data['body'] ?? '',
            'icon' => $n->data['icon'] ?? 'bell',
            'has_url' => ! empty($n->data['url']),
            'read' => $n->read_at !== null,
            'time' => $n->created_at->diffForHumans(),
        ];
    }
}

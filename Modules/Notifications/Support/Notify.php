<?php

namespace Modules\Notifications\Support;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Send an in-app notification to one or more users.
 *
 * Other modules just call Notify::send(); rows are stored in Laravel's
 * notifications table so other channels (WhatsApp, email) can read the
 * same table later. With $key, the same event never produces a
 * duplicate notification for the same user.
 *
 * @param  User|iterable<User>  $users
 */
class Notify
{
    public static function send(User|iterable $users, string $title, string $body = '', ?string $url = null, string $icon = 'bell', ?string $key = null): void
    {
        $users = $users instanceof User ? [$users] : $users;

        foreach ($users as $user) {
            if (! $user instanceof User || $user->status !== 'active') {
                continue;
            }

            if ($key && $user->notifications()->where('dedupe_key', $key)->exists()) {
                continue;
            }

            try {
                $user->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => 'lms',
                    'data' => ['title' => $title, 'body' => Str::limit($body, 160), 'url' => $url, 'icon' => $icon],
                    'dedupe_key' => $key,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Already sent by a concurrent process.
            }
        }
    }
}

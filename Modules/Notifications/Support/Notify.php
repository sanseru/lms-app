<?php

namespace Modules\Notifications\Support;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Kirim notifikasi in-app ke satu atau beberapa user.
 *
 * Modul lain cukup memanggil Notify::send(); baris disimpan di tabel
 * notifications bawaan Laravel sehingga kanal lain (WhatsApp, email)
 * nanti bisa membaca dari tabel yang sama. Dengan $key, kejadian yang
 * sama tidak menghasilkan notifikasi ganda untuk user yang sama.
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
                // Sudah dikirim oleh proses lain bersamaan.
            }
        }
    }
}

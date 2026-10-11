<?php

namespace Modules\Sessions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Sessions\Models\ClassSession;

/**
 * Feed iCal jadwal sesi per user, untuk dilanggan di Google Calendar,
 * Apple Calendar, atau Outlook. Diakses dengan token rahasia tanpa login.
 */
class CalendarController extends Controller
{
    public function feed(string $token): Response
    {
        $user = User::where('calendar_token', $token)->where('status', 'active')->firstOrFail();

        $sessions = ClassSession::query()
            ->visibleTo($user)
            ->whereIn('status', ['scheduled', 'completed'])
            ->whereBetween('starts_at', [now()->subDays(30), now()->addDays(120)])
            ->with('classRoom:id,name,zoom_url')
            ->orderBy('starts_at')
            ->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Alprogmatics//LMS//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape(config('app.name').' – Jadwal '.$user->name),
            'X-WR-TIMEZONE:Asia/Jakarta',
        ];

        foreach ($sessions as $session) {
            $zoom = $session->zoomLink();
            $lines = [
                ...$lines,
                'BEGIN:VEVENT',
                'UID:session-'.$session->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
                'DTSTAMP:'.$session->updated_at->utc()->format('Ymd\THis\Z'),
                'DTSTART:'.$session->starts_at->utc()->format('Ymd\THis\Z'),
                'DTEND:'.$session->ends_at->utc()->format('Ymd\THis\Z'),
                'SUMMARY:'.self::escape(($session->classRoom?->name ?? 'Sesi kelas').($session->topic ? " · {$session->topic}" : '')),
                'DESCRIPTION:'.self::escape(collect([$zoom ? "Zoom: {$zoom}" : null, 'Detail: '.route('sessions.show', $session)])->filter()->implode("\n")),
                ...($zoom ? ['LOCATION:'.self::escape($zoom)] : []),
                'URL:'.route('sessions.show', $session),
                'STATUS:CONFIRMED',
                'END:VEVENT',
            ];
        }

        $lines[] = 'END:VCALENDAR';

        return response(collect($lines)->map(self::fold(...))->implode("\r\n")."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="jadwal.ics"',
        ]);
    }

    /** Buat atau ganti token (tautan lama berhenti bekerja). */
    public function regenerate(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['calendar_token' => Str::random(40)])->save();

        return back()->with('success', 'Tautan kalender baru dibuat. Tautan lama tidak berlaku lagi.');
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
    }

    /** Baris iCal maksimal 75 oktet; lanjutan diawali spasi. */
    private static function fold(string $line): string
    {
        $out = [];
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--; // jangan memotong karakter UTF-8 multibyte
            }
            $out[] = substr($line, 0, $cut);
            $line = ' '.substr($line, $cut);
        }
        $out[] = $line;

        return implode("\r\n", $out);
    }
}

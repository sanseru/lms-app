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
 * Per-user iCal feed of session schedules, to subscribe to in Google Calendar,
 * Apple Calendar or Outlook. Accessed with a secret token, no login.
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
            'PRODID:-//Alprogmatics//LMS//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape(config('app.name').' – '.$user->name.' schedule'),
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
                'SUMMARY:'.self::escape(($session->classRoom?->name ?? 'Class session').($session->topic ? " · {$session->topic}" : '')),
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
            'Content-Disposition' => 'inline; filename="schedule.ics"',
        ]);
    }

    /** Create or replace the token (the old link stops working). */
    public function regenerate(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['calendar_token' => Str::random(40)])->save();

        return back()->with('success', 'New calendar link created. The old link no longer works.');
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
    }

    /** iCal lines are at most 75 octets; continuations start with a space. */
    private static function fold(string $line): string
    {
        $out = [];
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--; // never split a multibyte UTF-8 character
            }
            $out[] = substr($line, 0, $cut);
            $line = ' '.substr($line, $cut);
        }
        $out[] = $line;

        return implode("\r\n", $out);
    }
}

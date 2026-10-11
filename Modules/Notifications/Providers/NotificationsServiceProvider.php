<?php

namespace Modules\Notifications\Providers;

use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Notifications\Console\RemindSessionReportsCommand;
use Modules\Notifications\Http\Controllers\NotificationController;
use Modules\Notifications\Listeners\NotifyActivity;
use Modules\Sessions\Events\SessionCompleted;

/**
 * Notifikasi in-app (lonceng di header + halaman /notifikasi).
 *
 * Modul ini hanya mendengarkan event dan perubahan model modul lain,
 * sehingga modul sumber tidak perlu tahu notifikasi itu ada.
 */
class NotificationsServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Notifications';
    }

    public function boot(): void
    {
        parent::boot();

        $notify = fn () => $this->app->make(NotifyActivity::class);

        Event::listen(SessionCompleted::class, fn (SessionCompleted $e) => $notify()->sessionCompleted($e->session));
        Invoice::created(fn (Invoice $invoice) => $notify()->invoiceCreated($invoice));
        Payment::created(fn (Payment $payment) => $notify()->paymentSubmitted($payment));
        Payment::updated(fn (Payment $payment) => $notify()->paymentProcessed($payment));
        AssignmentSubmission::updated(fn (AssignmentSubmission $submission) => $notify()->submissionReviewed($submission));
        Question::created(fn (Question $question) => $notify()->questionAsked($question));
        Answer::created(fn (Answer $answer) => $notify()->questionAnswered($answer));

        // Lonceng di header: jumlah belum dibaca + 6 terbaru.
        Inertia::share('notifications', function () {
            $user = auth()->user();

            return $user ? [
                'unread' => $user->unreadNotifications()->count(),
                'latest' => $user->notifications()->take(6)->get()->map(NotificationController::toItem(...)),
            ] : null;
        });

        if ($this->app->runningInConsole()) {
            $this->commands([RemindSessionReportsCommand::class]);
        }
    }
}

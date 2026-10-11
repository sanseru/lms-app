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
 * In-app notifications (bell in the header + the /notifications page).
 *
 * This module only listens to other modules' events and model changes,
 * so source modules do not need to know notifications exist.
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

        // Header bell: unread count + 6 latest.
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

<?php

namespace Modules\Notifications\Listeners;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Notifications\Support\Notify;
use Modules\Sessions\Models\ClassSession;
use Modules\Sessions\Models\SessionAttendance;

/**
 * Turns events in other modules into notifications for the people who need to know.
 * Every notification uses a unique key, so re-triggered events
 * (e.g. an attendance correction) never send duplicates.
 */
class NotifyActivity
{
    /** Session summary for parents, homework for students. */
    public function sessionCompleted(ClassSession $session): void
    {
        $session->loadMissing(['classRoom:id,name', 'attendances.student.guardians']);
        $url = route('sessions.show', $session);
        $class = $session->classRoom?->name ?? 'Class';

        foreach ($session->attendances as $attendance) {
            $student = $attendance->student;
            if (! $student) {
                continue;
            }

            $status = SessionAttendance::STATUSES[$attendance->status] ?? $attendance->status;
            $body = collect([
                "{$student->name}: {$status}.",
                $session->topic ? "Lesson: {$session->topic}." : null,
                $session->parent_note,
            ])->filter()->implode(' ');

            Notify::send($student->guardians, "Session summary: {$class}", $body, $url, 'clipboard-check', "session-report:{$session->id}:{$student->id}");

            if ($session->homework) {
                Notify::send($student, "Homework from {$class}", $session->homework, $url, 'book-open', "session-homework:{$session->id}");
            }
        }
    }

    public function invoiceCreated(Invoice $invoice): void
    {
        $invoice->loadMissing('student.guardians');

        Notify::send(
            $invoice->student?->guardians ?? [],
            "New invoice {$invoice->number}",
            "{$invoice->title} for {$invoice->student?->name}, Rp".number_format($invoice->amount, 0, ',', '.').', due '.$invoice->due_date->translatedFormat('d M Y').'.',
            route('finance.invoices.show', $invoice),
            'ticket',
            "invoice-created:{$invoice->id}",
        );
    }

    /** A parent sent a transfer proof: admins need to verify it. Payments recorded by an admin are reported to parents right away. */
    public function paymentSubmitted(Payment $payment): void
    {
        if ($payment->status === 'verified') {
            $this->paymentResult($payment);

            return;
        }

        if ($payment->status !== 'pending') {
            return;
        }

        $payment->loadMissing('invoice.student');

        Notify::send(
            User::role('admin')->get(),
            'Transfer proof waiting for verification',
            "{$payment->invoice->number} · {$payment->invoice->student?->name} · Rp".number_format($payment->amount, 0, ',', '.'),
            route('finance.invoices.show', $payment->invoice_id),
            'hand-coins',
            "payment-submitted:{$payment->id}",
        );
    }

    public function paymentProcessed(Payment $payment): void
    {
        if ($payment->wasChanged('status') && in_array($payment->status, ['verified', 'rejected'])) {
            $this->paymentResult($payment);
        }
    }

    private function paymentResult(Payment $payment): void
    {
        $payment->loadMissing('invoice.student.guardians', 'submitter');
        $verified = $payment->status === 'verified';

        $recipients = $this->unique([$payment->submitter, ...($payment->invoice->student?->guardians ?? [])])
            ->reject(fn (User $u) => $u->id === $payment->verified_by);

        Notify::send(
            $recipients,
            $verified ? 'Payment received' : 'Payment rejected',
            $verified
                ? 'Your payment of Rp'.number_format($payment->amount, 0, ',', '.')." for {$payment->invoice->number} has been verified. Thank you."
                : "The transfer proof for {$payment->invoice->number} was rejected: {$payment->rejection_reason}",
            route('finance.invoices.show', $payment->invoice_id),
            $verified ? 'check' : 'alert-triangle',
            "payment-{$payment->status}:{$payment->id}",
        );
    }

    public function submissionReviewed(AssignmentSubmission $submission): void
    {
        if (! $submission->wasChanged('graded_at') || ! in_array($submission->status, ['graded', 'revision'])) {
            return;
        }

        $submission->loadMissing('lesson:id,title', 'student.guardians');
        $graded = $submission->status === 'graded';
        $title = $submission->lesson?->title ?? 'Assignment';

        Notify::send(
            $this->unique([$submission->student, ...($submission->student?->guardians ?? [])]),
            $graded ? "Assignment graded: {$title}" : "Assignment needs revision: {$title}",
            collect([$graded ? "Score: {$submission->score}." : null, $submission->feedback])->filter()->implode(' '),
            route('learning.lessons.show', $submission->lesson_id),
            $graded ? 'check' : 'arrow-left',
            "submission-{$submission->status}:{$submission->id}:{$submission->graded_at?->timestamp}",
        );
    }

    /** New question: the class teacher is notified. */
    public function questionAsked(Question $question): void
    {
        $question->loadMissing('classRoom.teacher');
        $teacher = $question->classRoom?->teacher;

        if ($teacher && $teacher->id !== $question->author_id) {
            Notify::send($teacher, "New question in {$question->classRoom->name}", $question->title, route('forum.questions.show', $question), 'message-circle', "question:{$question->id}");
        }
    }

    /** New answer: the asker is notified. */
    public function questionAnswered(Answer $answer): void
    {
        $answer->loadMissing('question.author', 'author');
        $question = $answer->question;

        if ($question?->author && $question->author_id !== $answer->author_id) {
            $who = $answer->by_staff ? 'Your teacher' : 'A classmate';
            Notify::send($question->author, "{$who} answered your question", $question->title, route('forum.questions.show', $question), 'message-circle', "answer:{$answer->id}");
        }
    }

    /** @param  array<int, User|null>  $users */
    private function unique(array $users): Collection
    {
        return collect($users)->filter()->unique('id')->values();
    }
}

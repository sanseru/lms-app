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
 * Menerjemahkan kejadian di modul lain menjadi notifikasi untuk orang yang perlu tahu.
 * Setiap notifikasi memakai kunci unik, jadi kejadian yang terpicu ulang
 * (mis. koreksi absensi) tidak mengirim notifikasi ganda.
 */
class NotifyActivity
{
    /** Ringkasan sesi untuk orang tua, PR untuk siswa. */
    public function sessionCompleted(ClassSession $session): void
    {
        $session->loadMissing(['classRoom:id,name', 'attendances.student.guardians']);
        $url = route('sessions.show', $session);
        $class = $session->classRoom?->name ?? 'Kelas';

        foreach ($session->attendances as $attendance) {
            $student = $attendance->student;
            if (! $student) {
                continue;
            }

            $status = SessionAttendance::STATUSES[$attendance->status] ?? $attendance->status;
            $body = collect([
                "{$student->name}: {$status}.",
                $session->topic ? "Materi: {$session->topic}." : null,
                $session->parent_note,
            ])->filter()->implode(' ');

            Notify::send($student->guardians, "Ringkasan sesi {$class}", $body, $url, 'clipboard-check', "session-report:{$session->id}:{$student->id}");

            if ($session->homework) {
                Notify::send($student, "PR dari sesi {$class}", $session->homework, $url, 'book-open', "session-homework:{$session->id}");
            }
        }
    }

    public function invoiceCreated(Invoice $invoice): void
    {
        $invoice->loadMissing('student.guardians');

        Notify::send(
            $invoice->student?->guardians ?? [],
            "Tagihan baru {$invoice->number}",
            "{$invoice->title} untuk {$invoice->student?->name}, Rp".number_format($invoice->amount, 0, ',', '.').', jatuh tempo '.$invoice->due_date->translatedFormat('d M Y').'.',
            route('finance.invoices.show', $invoice),
            'ticket',
            "invoice-created:{$invoice->id}",
        );
    }

    /** Orang tua mengirim bukti transfer: admin perlu memverifikasi. Pembayaran yang dicatat admin langsung dikabarkan ke orang tua. */
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
            'Bukti transfer menunggu verifikasi',
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
            $verified ? 'Pembayaran diterima' : 'Pembayaran ditolak',
            $verified
                ? 'Pembayaran Rp'.number_format($payment->amount, 0, ',', '.')." untuk {$payment->invoice->number} sudah diverifikasi. Terima kasih."
                : "Bukti transfer untuk {$payment->invoice->number} ditolak: {$payment->rejection_reason}",
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
        $title = $submission->lesson?->title ?? 'Tugas';

        Notify::send(
            $this->unique([$submission->student, ...($submission->student?->guardians ?? [])]),
            $graded ? "Tugas dinilai: {$title}" : "Tugas perlu revisi: {$title}",
            collect([$graded ? "Nilai {$submission->score}." : null, $submission->feedback])->filter()->implode(' '),
            route('learning.lessons.show', $submission->lesson_id),
            $graded ? 'check' : 'arrow-left',
            "submission-{$submission->status}:{$submission->id}:{$submission->graded_at?->timestamp}",
        );
    }

    /** Pertanyaan baru: guru kelas diberi tahu. */
    public function questionAsked(Question $question): void
    {
        $question->loadMissing('classRoom.teacher');
        $teacher = $question->classRoom?->teacher;

        if ($teacher && $teacher->id !== $question->author_id) {
            Notify::send($teacher, "Pertanyaan baru di {$question->classRoom->name}", $question->title, route('forum.questions.show', $question), 'message-circle', "question:{$question->id}");
        }
    }

    /** Jawaban baru: penanya diberi tahu. */
    public function questionAnswered(Answer $answer): void
    {
        $answer->loadMissing('question.author', 'author');
        $question = $answer->question;

        if ($question?->author && $question->author_id !== $answer->author_id) {
            $who = $answer->by_staff ? 'Guru' : 'Temanmu';
            Notify::send($question->author, "{$who} menjawab pertanyaanmu", $question->title, route('forum.questions.show', $question), 'message-circle', "answer:{$answer->id}");
        }
    }

    /** @param  array<int, User|null>  $users */
    private function unique(array $users): Collection
    {
        return collect($users)->filter()->unique('id')->values();
    }
}

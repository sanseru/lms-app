<?php

use App\Models\User;
use Modules\Classes\Models\ClassRoom;
use Modules\Leads\Models\Lead;

beforeEach(function () {
    $this->admin = userWithRole('admin');
    $this->teacher = userWithRole('teacher', ['name' => 'Pak Budi']);
});

function applyForm(array $overrides = []): array
{
    return $overrides + [
        'parent_name' => 'Ibu Rina', 'phone' => '0812-3456-7890', 'child_name' => 'Dika', 'child_age' => 9,
        'interest' => 'Scratch Junior & Scratch', 'source' => 'instagram', 'preferred_time' => 'Sabtu pagi',
    ];
}

test('parents can sign up for a free trial from the website and admins are notified', function () {
    $this->get('/coba-gratis')->assertOk()->assertInertia(fn ($page) => $page->component('Leads::Apply'));

    $this->post('/coba-gratis', applyForm())->assertRedirect('/coba-gratis')->assertSessionHas('lead_sent', 'Ibu Rina');
    $this->post('/coba-gratis', applyForm()); // kirim dua kali

    expect(Lead::count())->toBe(1)
        ->and(Lead::first()->status)->toBe('new')
        ->and(Lead::first()->waNumber())->toBe('6281234567890')
        ->and($this->admin->notifications()->count())->toBe(1);
});

test('the signup form validates and silently drops bots', function () {
    $this->post('/coba-gratis', applyForm(['phone' => 'abc', 'child_name' => '']))->assertSessionHasErrors(['phone', 'child_name']);
    $this->post('/coba-gratis', applyForm(['website' => 'http://spam.example']))->assertSessionHas('lead_sent');

    expect(Lead::count())->toBe(0);
});

test('admin moves a lead through contact, trial and conversion', function () {
    $this->post('/coba-gratis', applyForm());
    $lead = Lead::first();
    $class = ClassRoom::create([
        'code' => 'SCR-001', 'name' => 'Scratch Sabtu', 'subject' => 'Scratch', 'teacher_id' => $this->teacher->id,
        'schedule_days' => [6], 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'active',
    ]);

    $this->actingAs($this->admin)->post("/calon-siswa/{$lead->id}/dihubungi")->assertSessionHas('success');
    $this->actingAs($this->admin)->post("/calon-siswa/{$lead->id}/jadwal-trial", [
        'trial_at' => now()->addDays(2)->format('Y-m-d\TH:i'), 'trial_teacher_id' => $this->teacher->id,
    ])->assertSessionHas('success');

    expect($lead->fresh()->status)->toBe('trial_scheduled')
        ->and($this->teacher->notifications()->first()->data['title'])->toBe('Kelas trial: Dika');

    $this->actingAs($this->teacher)->get('/calon-siswa/trial')
        ->assertInertia(fn ($page) => $page->component('Leads::Trials')->has('upcoming', 1));

    $this->actingAs($this->teacher)->post("/calon-siswa/{$lead->id}/hasil-trial", [
        'trial_result' => 'Cepat paham, membuat kucing berlari.', 'trial_interest' => 5, 'recommended_class_id' => $class->id,
    ])->assertSessionHas('success');
    expect($lead->fresh()->status)->toBe('trial_done');

    $this->actingAs($this->admin)->get("/calon-siswa/{$lead->id}")
        ->assertInertia(fn ($page) => $page->component('Leads::Show')->where('suggested.student', 'dika')->where('existingGuardian', null));

    $this->actingAs($this->admin)->post("/calon-siswa/{$lead->id}/jadikan-siswa", [
        'student_username' => 'dika', 'guardian_username' => 'ibu.rina', 'password' => 'rahasia123', 'relationship' => 'ibu', 'class_room_id' => $class->id,
    ])->assertSessionHasErrors('guardian_username'); // titik tidak diizinkan

    $this->actingAs($this->admin)->post("/calon-siswa/{$lead->id}/jadikan-siswa", [
        'student_username' => 'dika', 'guardian_username' => 'ibu_rina', 'password' => 'rahasia123', 'relationship' => 'ibu', 'class_room_id' => $class->id,
    ])->assertRedirect("/calon-siswa/{$lead->id}");

    $student = User::where('username', 'dika')->firstOrFail();
    $guardian = User::where('username', 'ibu_rina')->firstOrFail();
    expect($student->hasRole('student'))->toBeTrue()
        ->and($guardian->hasRole('guardian'))->toBeTrue()
        ->and($guardian->children()->pluck('users.id')->all())->toBe([$student->id])
        ->and($class->students()->whereKey($student->id)->exists())->toBeTrue()
        ->and($lead->fresh()->status)->toBe('won')
        ->and($lead->fresh()->student_id)->toBe($student->id);

    $this->post('/logout');
    $this->post('/login', ['login' => 'ibu_rina', 'password' => 'rahasia123'])->assertRedirect(route('dashboard', absolute: false));
});

test('a sibling is added to the existing parent account', function () {
    $parent = userWithRole('guardian', ['phone' => '+62 812 3456 7890']);
    $this->post('/coba-gratis', applyForm(['child_name' => 'Adik']));
    $lead = Lead::first();

    $this->actingAs($this->admin)->get("/calon-siswa/{$lead->id}")
        ->assertInertia(fn ($page) => $page->where('existingGuardian.id', $parent->id));

    $this->actingAs($this->admin)->post("/calon-siswa/{$lead->id}/jadikan-siswa", [
        'student_username' => 'adik', 'password' => 'rahasia123', 'relationship' => 'ibu',
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($parent->children()->count())->toBe(1)
        ->and(User::role('guardian')->count())->toBe(1);
});

test('lost leads keep their reason and can be reopened', function () {
    $this->post('/coba-gratis', applyForm());
    $lead = Lead::first();

    $this->actingAs($this->admin)->post("/calon-siswa/{$lead->id}/tidak-lanjut", ['lost_reason' => 'Jadwal tidak cocok']);
    expect($lead->fresh())->status->toBe('lost')->lost_reason->toBe('Jadwal tidak cocok');

    $this->actingAs($this->admin)->post("/calon-siswa/{$lead->id}/buka-lagi");
    expect($lead->fresh()->status)->toBe('new');

    $this->actingAs($this->admin)->get('/calon-siswa')
        ->assertInertia(fn ($page) => $page->component('Leads::Index')->has('columns.0.leads', 1)->where('stats.new_month', 1));
});

test('only admins manage leads and teachers only record their own trials', function () {
    $this->post('/coba-gratis', applyForm());
    $lead = Lead::first();
    $lead->update(['status' => 'trial_scheduled', 'trial_at' => now()->subHour(), 'trial_teacher_id' => $this->teacher->id]);
    $otherTeacher = userWithRole('teacher');

    $this->actingAs($this->teacher)->get('/calon-siswa')->assertForbidden();
    $this->actingAs($this->teacher)->get("/calon-siswa/{$lead->id}")->assertForbidden();
    $this->actingAs(userWithRole('guardian'))->get('/calon-siswa/trial')->assertForbidden();
    $this->actingAs($otherTeacher)->post("/calon-siswa/{$lead->id}/hasil-trial", ['trial_result' => 'x', 'trial_interest' => 3])->assertForbidden();
    $this->actingAs($otherTeacher)->get('/calon-siswa/trial')->assertInertia(fn ($page) => $page->has('upcoming', 0));
});

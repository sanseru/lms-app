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
    $this->get('/free-trial')->assertOk()->assertInertia(fn ($page) => $page->component('Leads::Apply'));

    $this->post('/free-trial', applyForm())->assertRedirect('/free-trial')->assertSessionHas('lead_sent', 'Ibu Rina');
    $this->post('/free-trial', applyForm()); // submitted twice

    expect(Lead::count())->toBe(1)
        ->and(Lead::first()->status)->toBe('new')
        ->and(Lead::first()->waNumber())->toBe('6281234567890')
        ->and($this->admin->notifications()->count())->toBe(1);
});

test('the signup form validates and silently drops bots', function () {
    $this->post('/free-trial', applyForm(['phone' => 'abc', 'child_name' => '']))->assertSessionHasErrors(['phone', 'child_name']);
    $this->post('/free-trial', applyForm(['website' => 'http://spam.example']))->assertSessionHas('lead_sent');

    expect(Lead::count())->toBe(0);
});

test('admin moves a lead through contact, trial and conversion', function () {
    $this->post('/free-trial', applyForm());
    $lead = Lead::first();
    $class = ClassRoom::create([
        'code' => 'SCR-001', 'name' => 'Scratch Sabtu', 'subject' => 'Scratch', 'teacher_id' => $this->teacher->id,
        'schedule_days' => [6], 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'active',
    ]);

    $this->actingAs($this->admin)->post("/leads/{$lead->id}/contacted")->assertSessionHas('success');
    $this->actingAs($this->admin)->post("/leads/{$lead->id}/schedule-trial", [
        'trial_at' => now()->addDays(2)->format('Y-m-d\TH:i'), 'trial_teacher_id' => $this->teacher->id,
    ])->assertSessionHas('success');

    expect($lead->fresh()->status)->toBe('trial_scheduled')
        ->and($this->teacher->notifications()->first()->data['title'])->toBe('Trial class: Dika');

    $this->actingAs($this->teacher)->get('/leads/trials')
        ->assertInertia(fn ($page) => $page->component('Leads::Trials')->has('upcoming', 1));

    $this->actingAs($this->teacher)->post("/leads/{$lead->id}/trial-result", [
        'trial_result' => 'Cepat paham, membuat kucing berlari.', 'trial_interest' => 5, 'recommended_class_id' => $class->id,
    ])->assertSessionHas('success');
    expect($lead->fresh()->status)->toBe('trial_done');

    $this->actingAs($this->admin)->get("/leads/{$lead->id}")
        ->assertInertia(fn ($page) => $page->component('Leads::Show')->where('suggested.student', 'dika')->where('existingGuardian', null));

    $this->actingAs($this->admin)->post("/leads/{$lead->id}/convert", [
        'student_username' => 'dika', 'guardian_username' => 'ibu.rina', 'password' => 'rahasia123', 'relationship' => 'ibu', 'class_room_id' => $class->id,
    ])->assertSessionHasErrors('guardian_username'); // dots are not allowed

    $this->actingAs($this->admin)->post("/leads/{$lead->id}/convert", [
        'student_username' => 'dika', 'guardian_username' => 'ibu_rina', 'password' => 'rahasia123', 'relationship' => 'ibu', 'class_room_id' => $class->id,
    ])->assertRedirect("/leads/{$lead->id}");

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
    $this->post('/free-trial', applyForm(['child_name' => 'Adik']));
    $lead = Lead::first();

    $this->actingAs($this->admin)->get("/leads/{$lead->id}")
        ->assertInertia(fn ($page) => $page->where('existingGuardian.id', $parent->id));

    $this->actingAs($this->admin)->post("/leads/{$lead->id}/convert", [
        'student_username' => 'adik', 'password' => 'rahasia123', 'relationship' => 'ibu',
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($parent->children()->count())->toBe(1)
        ->and(User::role('guardian')->count())->toBe(1);
});

test('lost leads keep their reason and can be reopened', function () {
    $this->post('/free-trial', applyForm());
    $lead = Lead::first();

    $this->actingAs($this->admin)->post("/leads/{$lead->id}/lost", ['lost_reason' => 'Jadwal tidak cocok']);
    expect($lead->fresh())->status->toBe('lost')->lost_reason->toBe('Jadwal tidak cocok');

    $this->actingAs($this->admin)->post("/leads/{$lead->id}/reopen");
    expect($lead->fresh()->status)->toBe('new');

    $this->actingAs($this->admin)->get('/leads')
        ->assertInertia(fn ($page) => $page->component('Leads::Index')->has('columns.0.leads', 1)->where('stats.new_month', 1));
});

test('only admins manage leads and teachers only record their own trials', function () {
    $this->post('/free-trial', applyForm());
    $lead = Lead::first();
    $lead->update(['status' => 'trial_scheduled', 'trial_at' => now()->subHour(), 'trial_teacher_id' => $this->teacher->id]);
    $otherTeacher = userWithRole('teacher');

    $this->actingAs($this->teacher)->get('/leads')->assertForbidden();
    $this->actingAs($this->teacher)->get("/leads/{$lead->id}")->assertForbidden();
    $this->actingAs(userWithRole('guardian'))->get('/leads/trials')->assertForbidden();
    $this->actingAs($otherTeacher)->post("/leads/{$lead->id}/trial-result", ['trial_result' => 'x', 'trial_interest' => 3])->assertForbidden();
    $this->actingAs($otherTeacher)->get('/leads/trials')->assertInertia(fn ($page) => $page->has('upcoming', 0));
});

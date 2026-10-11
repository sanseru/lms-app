<?php

use App\Models\User;

test('students can log in with their username', function () {
    $student = userWithRole('student', ['username' => 'aldi', 'email' => null]);

    $this->post('/login', ['login' => 'aldi', 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($student);
    expect($student->fresh()->last_login_at)->not->toBeNull();
});

test('inactive accounts cannot log in', function () {
    userWithRole('student', ['username' => 'nonaktif', 'status' => 'inactive']);

    $this->post('/login', ['login' => 'nonaktif', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();
});

test('each role sees its own dashboard', function (string $role, string $component) {
    $this->actingAs(userWithRole($role))
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['admin', 'Dashboard::Admin'],
    ['teacher', 'Dashboard::Teacher'],
    ['student', 'Dashboard::Student'],
    ['guardian', 'Dashboard::Guardian'],
]);

test('navigation only contains menus allowed for the role', function () {
    $labels = fn (User $user) => collect($this->actingAs($user)->get('/dashboard')->viewData('page')['props']['navigation'])
        ->flatMap(fn ($group) => collect($group['items'])->pluck('label'))
        ->all();

    expect($labels(userWithRole('admin')))->toContain('Siswa', 'Guru', 'Orang Tua', 'Kelas', 'Tagihan');
    expect($labels(userWithRole('student')))->toBe(['Beranda', 'Kelas', 'Jadwal', 'Materi Saya', 'Proyekku', 'Tanya Jawab']);
    expect($labels(userWithRole('guardian')))->toContain('Tagihan Anak')->not->toContain('Siswa');
});

test('non admins cannot open management pages', function (string $role) {
    $user = userWithRole($role);

    foreach (['/people/students', '/people/teachers', '/people/guardians', '/classes/create', '/finance', '/finance/invoices/create'] as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
    }
})->with(['teacher', 'student', 'guardian']);

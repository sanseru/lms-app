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

test('validation and login errors are in English', function () {
    expect(app()->getLocale())->toBe('en');

    $this->post('/login', ['login' => 'aldi'])
        ->assertSessionHasErrors(['password' => 'The password field is required.']);

    userWithRole('student', ['username' => 'aldi']);
    $this->post('/login', ['login' => 'aldi', 'password' => 'wrong'])
        ->assertSessionHasErrors(['login' => 'Incorrect username/email or password.']);
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

test('navigation groups are English and ordered', function () {
    $groups = collect($this->actingAs(userWithRole('admin'))->get('/dashboard')->viewData('page')['props']['navigation'])->pluck('label')->all();

    expect($groups[0])->toBe('Main')
        ->and(array_values(array_intersect(['Main', 'Academic', 'Learning', 'People', 'Finance', 'Other'], $groups)))->toBe($groups);
});

test('navigation only contains menus allowed for the role', function () {
    $labels = fn (User $user) => collect($this->actingAs($user)->get('/dashboard')->viewData('page')['props']['navigation'])
        ->flatMap(fn ($group) => collect($group['items'])->pluck('label'))
        ->all();

    expect($labels(userWithRole('admin')))->toContain('Students', 'Teachers', 'Parents', 'Classes', 'Invoices', 'Monthly reports', 'Audit log');
    // Students use the client's menu names (Online Lesson, Archive); the header shows Home, Course,
    // Online Lesson, Community, Laboratory and Archive first and the rest under "More".
    expect($labels(userWithRole('student')))->toBe(['Home', 'Online Lesson', 'Course', 'Community', 'Laboratory', 'My projects', 'Q&A', 'My achievements', 'My certificates', 'My monthly reports', 'Archive']);
    expect($labels(userWithRole('teacher')))->toContain('Course', 'Online Lesson')->not->toContain('Courses & lessons', 'Sessions & attendance');
    expect($labels(userWithRole('guardian')))->toContain("Child's invoices", 'Monthly reports')->not->toContain('Students', 'Audit log');
});

test('non admins cannot open management pages', function (string $role) {
    $user = userWithRole($role);

    foreach (['/people/students', '/people/teachers', '/people/guardians', '/classes/create', '/finance', '/finance/invoices/create'] as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
    }
})->with(['teacher', 'student', 'guardian']);

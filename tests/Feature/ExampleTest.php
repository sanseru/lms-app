<?php

test('home redirects guests to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('home redirects signed in users to the dashboard', function () {
    $this->actingAs(userWithRole('student'))->get('/')->assertRedirect(route('dashboard'));
});

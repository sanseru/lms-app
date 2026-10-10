<?php

test('landing page is public', function () {
    $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Landing::Home'));
});

test('landing page is reachable when signed in', function () {
    $this->actingAs(userWithRole('student'))->get('/')->assertOk();
});

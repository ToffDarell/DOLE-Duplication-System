<?php

test('login page returns a successful response', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});

test('unauthenticated users are redirected to login', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

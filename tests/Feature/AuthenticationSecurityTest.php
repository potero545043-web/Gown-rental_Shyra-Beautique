<?php

it('requires authentication before opening customer and staff pages', function () {
    foreach ([
        '/customer/dashboard',
        '/customer/catalog',
        '/employee/dashboard',
        '/employee/reservations',
        '/owner/dashboard',
        '/owner/customers',
    ] as $path) {
        $this->get($path)->assertRedirect(route('login'));
    }
});

it('redirects production HTTP requests to HTTPS', function () {
    app()->instance('env', 'production');

    $this->get('http://example.com/login')
        ->assertRedirect('https://example.com/login');

    $this->post('http://example.com/login', ['email' => 'customer@example.com', 'password' => 'secret'])
        ->assertStatus(308)
        ->assertRedirect('https://example.com/login');
});

it('keeps local loopback URLs on HTTP for development', function () {
    app()->instance('env', 'production');

    $this->get('http://127.0.0.1:8000/login')->assertOk();
});

it('rejects plain-text passwords in POST requests', function () {
    $this->post('/login', [
        'email' => 'customer@example.com',
        'password' => 'plaintext-must-not-be-accepted',
    ])->assertSessionHasErrors('password');
});

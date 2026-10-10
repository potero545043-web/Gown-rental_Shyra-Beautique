<?php

return [
    'login_password_private_key_path' => env(
        'LOGIN_PASSWORD_PRIVATE_KEY_PATH',
        storage_path('app/private/login-password-private.pem')
    ),
    'login_password_private_key_base64' => env('LOGIN_PASSWORD_PRIVATE_KEY_BASE64'),
];

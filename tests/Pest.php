<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function encryptPasswordFields(array $fields): array
{
    static $keyPath = null;
    static $publicKey = null;

    if ($keyPath === null) {
        $configuredPath = config('security.login_password_private_key_path');

        if (is_string($configuredPath) && is_file($configuredPath)) {
            $keyPath = $configuredPath;
        } else {
            $key = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);
            expect($key)->not->toBeFalse();
            expect(openssl_pkey_export($key, $privateKey))->toBeTrue();

            $keyPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gown-rental-test-key-' . getmypid() . '.pem';
            expect(file_put_contents($keyPath, $privateKey, LOCK_EX))->not->toBeFalse();
            register_shutdown_function(static function () use (&$keyPath): void {
                if ($keyPath !== null && is_file($keyPath) && str_contains(basename($keyPath), 'test-key')) {
                    unlink($keyPath);
                }
            });
        }

        config(['security.login_password_private_key_path' => $keyPath]);
        $privateKey = openssl_pkey_get_private(file_get_contents($keyPath));
        expect($privateKey)->not->toBeFalse();
        $details = openssl_pkey_get_details($privateKey);
        expect($details)->not->toBeFalse();
        $publicKey = $details['key'];
    }

    config(['security.login_password_private_key_path' => $keyPath]);

    foreach (['password', 'password_confirmation', 'current_password'] as $field) {
        if (!array_key_exists($field, $fields)) {
            continue;
        }

        $aesKey = random_bytes(32);
        $iv = random_bytes(12);
        $ciphertext = openssl_encrypt($fields[$field], 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);
        expect($ciphertext)->not->toBeFalse();
        expect(openssl_public_encrypt($aesKey, $encryptedKey, $publicKey, OPENSSL_PKCS1_OAEP_PADDING))->toBeTrue();

        $fields[$field . '_encrypted'] = base64_encode(json_encode([
            'key' => base64_encode($encryptedKey),
            'iv' => base64_encode($iv),
            'data' => base64_encode($ciphertext . $tag),
        ], JSON_THROW_ON_ERROR));
        unset($fields[$field]);
    }

    return $fields;
}

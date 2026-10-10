<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;

class GenerateLoginPasswordKeyPair extends Command
{
    protected $signature = 'security:generate-login-password-keypair {--force : Replace the existing private key} {--base64 : Print the private key as base64 for a secret environment variable}';

    protected $description = 'Generate the server-side key used to encrypt password form submissions';

    public function handle(): int
    {
        $path = config('security.login_password_private_key_path');

        if (is_file($path) && !$this->option('force')) {
            $this->error("A private key already exists at {$path}. Use --force only to replace it.");

            return self::FAILURE;
        }

        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            $this->error("Unable to create the key directory: {$directory}");

            return self::FAILURE;
        }

        $this->configureOpenSsl();

        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 3072,
        ]);

        if ($key === false || !openssl_pkey_export($key, $privateKey)) {
            $errors = [];
            while ($error = openssl_error_string()) {
                $errors[] = $error;
            }

            throw new RuntimeException('Unable to generate the login password encryption key pair.'
                . ($errors === [] ? '' : ' OpenSSL: ' . implode('; ', $errors)));
        }

        if (file_put_contents($path, $privateKey, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write the private key to {$path}.");
        }

        if (PHP_OS_FAMILY !== 'Windows' && !chmod($path, 0600)) {
            throw new RuntimeException("Unable to restrict permissions on {$path}.");
        }

        $this->info("Private key generated at {$path}.");
        $this->warn('Keep this key private and do not commit it.');

        if ($this->option('base64')) {
            $this->line('LOGIN_PASSWORD_PRIVATE_KEY_BASE64=' . base64_encode($privateKey));
        }

        return self::SUCCESS;
    }

    private function configureOpenSsl(): void
    {
        if (getenv('OPENSSL_CONF')) {
            return;
        }

        $phpDirectory = dirname(PHP_BINARY);

        foreach (['extras/ssl/openssl.cnf', 'extras/openssl/openssl.cnf'] as $relativePath) {
            $path = $phpDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (is_file($path)) {
                putenv('OPENSSL_CONF=' . $path);
                return;
            }
        }
    }
}

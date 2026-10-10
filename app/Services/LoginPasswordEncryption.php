<?php

namespace App\Services;

use RuntimeException;

class LoginPasswordEncryption
{
    public function publicKey(): ?string
    {
        $privateKey = $this->privateKey();

        if ($privateKey === null) {
            return null;
        }

        $details = openssl_pkey_get_details($privateKey);

        if ($details === false || !isset($details['key'])) {
            throw new RuntimeException('Unable to derive the password encryption public key.');
        }

        return $details['key'];
    }

    public function decrypt(string $envelope): ?string
    {
        $decodedEnvelope = base64_decode($envelope, true);
        $payload = $decodedEnvelope === false ? null : json_decode($decodedEnvelope, true);

        if (!is_array($payload) || !isset($payload['key'], $payload['iv'], $payload['data'])) {
            return null;
        }

        $encryptedKey = base64_decode($payload['key'], true);
        $iv = base64_decode($payload['iv'], true);
        $encryptedData = base64_decode($payload['data'], true);

        if ($encryptedKey === false || $iv === false || strlen($iv) !== 12
            || $encryptedData === false || strlen($encryptedData) < 17) {
            return null;
        }

        $privateKey = $this->privateKey();

        if ($privateKey === null) {
            throw new RuntimeException('Login password encryption is not configured. Generate a key pair before accepting passwords.');
        }

        if (!openssl_private_decrypt($encryptedKey, $aesKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)
            || strlen($aesKey) !== 32) {
            return null;
        }

        $tag = substr($encryptedData, -16);
        $ciphertext = substr($encryptedData, 0, -16);
        $password = openssl_decrypt($ciphertext, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);

        return $password === false ? null : $password;
    }

    private function privateKey(): mixed
    {
        $encodedKey = config('security.login_password_private_key_base64');
        if (is_string($encodedKey) && $encodedKey !== '') {
            $keyContents = base64_decode($encodedKey, true);

            if ($keyContents === false || $keyContents === '') {
                throw new RuntimeException('The configured login password private key is not valid base64.');
            }
        } else {
            $path = config('security.login_password_private_key_path');

            if (!is_file($path)) {
                return null;
            }

            $keyContents = file_get_contents($path);
        }

        if ($keyContents === false || $keyContents === '') {
            throw new RuntimeException('The configured login password private key could not be read.');
        }

        $privateKey = openssl_pkey_get_private($keyContents);

        if ($privateKey === false) {
            throw new RuntimeException('The configured login password private key is invalid.');
        }

        return $privateKey;
    }
}

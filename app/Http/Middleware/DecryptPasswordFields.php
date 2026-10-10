<?php

namespace App\Http\Middleware;

use App\Services\LoginPasswordEncryption;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DecryptPasswordFields
{
    private const PASSWORD_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->getRealMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $inputs = $request->request->all();
        $encryptedFields = [];

        foreach (array_keys($inputs) as $name) {
            if (str_ends_with($name, '_encrypted')) {
                $encryptedFields[substr($name, 0, -10)] = $name;
            }
        }

        foreach (self::PASSWORD_FIELDS as $field) {
            if (array_key_exists($field, $inputs)) {
                throw ValidationException::withMessages([
                    $field => 'Password fields must be encrypted before they are submitted. Refresh the page and try again.',
                ]);
            }
        }

        if ($encryptedFields === []) {
            return $next($request);
        }

        $encryption = app(LoginPasswordEncryption::class);

        foreach ($encryptedFields as $field => $encryptedName) {
            if (!in_array($field, self::PASSWORD_FIELDS, true)) {
                throw ValidationException::withMessages([
                    'password' => 'The encrypted password field is invalid.',
                ]);
            }

            $decrypted = $encryption->decrypt((string) $inputs[$encryptedName]);

            if ($decrypted === null) {
                throw ValidationException::withMessages([
                    $field => 'The encrypted password could not be read. Refresh the page and try again.',
                ]);
            }

            $request->request->remove($encryptedName);
            $request->request->set($field, $decrypted);
        }

        return $next($request);
    }
}

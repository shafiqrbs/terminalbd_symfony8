<?php

declare(strict_types=1);

namespace App\User\Util;

/**
 * Replaces fos_user.util.token_generator.
 *
 * Same shape as FOS\UserBundle\Util\TokenGenerator: 32 random bytes in
 * URL-safe base64 without padding, so tokens still fit the
 * confirmation_token column and the /resetting/reset/{token} route.
 */
final class TokenGenerator
{
    public function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}

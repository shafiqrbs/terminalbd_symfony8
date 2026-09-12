<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

/**
 * DBAL 4 removed the "array" type, but 23 live columns (plus fos_user.roles)
 * hold PHP-serialized payloads written by DBAL 2. This reproduces the old
 * behaviour byte-for-byte so the ported app reads production data unchanged.
 *
 * Do not "modernise" this to json without migrating the stored values first -
 * unserialize() and json_decode() are not interchangeable on existing rows.
 */
final class LegacyArrayType extends Type
{
    public const string NAME = 'array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : serialize($value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        // DBAL 2 suppressed unserialize() notices and raised a conversion error
        // instead; several legacy rows contain empty strings rather than NULL.
        set_error_handler(static fn (): bool => true);
        try {
            $decoded = unserialize((string) $value);
            if ($decoded === false && (string) $value !== serialize(false)) {
                throw ConversionException::conversionFailed((string) $value, self::NAME);
            }

            return $decoded;
        } finally {
            restore_error_handler();
        }
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

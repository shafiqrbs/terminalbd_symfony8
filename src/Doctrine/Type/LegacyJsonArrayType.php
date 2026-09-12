<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * DBAL 2's "json_array" type: JSON in the column, always an array in PHP,
 * with NULL/empty decoding to []. Removed in DBAL 3; one live column
 * (restaurant RestaurantTableInvoice.serveBy) still uses it.
 */
final class LegacyJsonArrayType extends Type
{
    public const string NAME = 'json_array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Form;

/**
 * Symfony 2 wrote choice arrays as value => label; Symfony 3 onwards reads
 * them as label => value.
 *
 * Literal choice arrays were flipped in place by
 * migration/bin/port-form-options.php. The ones built at runtime - the role
 * lists in particular - are flipped here instead, because the repository
 * methods that build them are also read by code that is not a form.
 *
 * Optgroups (a nested array under a group label) keep their group key.
 */
final class ChoiceList
{
    /**
     * @param array<string|int, mixed> $choices
     *
     * @return array<string|int, mixed>
     */
    public static function flip(array $choices): array
    {
        $flipped = [];

        foreach ($choices as $value => $label) {
            if (is_array($label)) {
                // An optgroup: the key is the group label, not a choice value.
                $flipped[$value] = self::flip($label);
                continue;
            }

            if (is_object($label)) {
                // Entity choices already have their labels supplied by the
                // choice_label option; leave the list untouched.
                return $choices;
            }

            $flipped[(string) $label] = $value;
        }

        return $flipped;
    }
}

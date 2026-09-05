<?php

declare(strict_types=1);

/**
 * Symfony 2 form API -> Symfony 8.
 *
 * Symfony 3 removed string type names, renamed the entity/choice `property`
 * option to `choice_label`, renamed CollectionType's `type`/`options` to
 * `entry_type`/`entry_options`, replaced setDefaultOptions() with
 * configureOptions() and getName() with getBlockPrefix().
 *
 * Idempotent: run it as often as you like.
 *
 * Usage: php8.5 migration/bin/port-forms.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

/** Symfony 2 short name => Symfony 8 form type FQCN. */
const TYPE_MAP = [
    'birthday' => 'Symfony\Component\Form\Extension\Core\Type\BirthdayType',
    'button' => 'Symfony\Component\Form\Extension\Core\Type\ButtonType',
    'checkbox' => 'Symfony\Component\Form\Extension\Core\Type\CheckboxType',
    'choice' => 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
    'collection' => 'Symfony\Component\Form\Extension\Core\Type\CollectionType',
    'country' => 'Symfony\Component\Form\Extension\Core\Type\CountryType',
    'currency' => 'Symfony\Component\Form\Extension\Core\Type\CurrencyType',
    'date' => 'Symfony\Component\Form\Extension\Core\Type\DateType',
    'datetime' => 'Symfony\Component\Form\Extension\Core\Type\DateTimeType',
    'email' => 'Symfony\Component\Form\Extension\Core\Type\EmailType',
    'entity' => 'Symfony\Bridge\Doctrine\Form\Type\EntityType',
    'file' => 'Symfony\Component\Form\Extension\Core\Type\FileType',
    'form' => 'Symfony\Component\Form\Extension\Core\Type\FormType',
    'hidden' => 'Symfony\Component\Form\Extension\Core\Type\HiddenType',
    'integer' => 'Symfony\Component\Form\Extension\Core\Type\IntegerType',
    'language' => 'Symfony\Component\Form\Extension\Core\Type\LanguageType',
    'locale' => 'Symfony\Component\Form\Extension\Core\Type\LocaleType',
    'money' => 'Symfony\Component\Form\Extension\Core\Type\MoneyType',
    'number' => 'Symfony\Component\Form\Extension\Core\Type\NumberType',
    'password' => 'Symfony\Component\Form\Extension\Core\Type\PasswordType',
    'percent' => 'Symfony\Component\Form\Extension\Core\Type\PercentType',
    'radio' => 'Symfony\Component\Form\Extension\Core\Type\RadioType',
    'range' => 'Symfony\Component\Form\Extension\Core\Type\RangeType',
    'repeated' => 'Symfony\Component\Form\Extension\Core\Type\RepeatedType',
    'reset' => 'Symfony\Component\Form\Extension\Core\Type\ResetType',
    'search' => 'Symfony\Component\Form\Extension\Core\Type\SearchType',
    'submit' => 'Symfony\Component\Form\Extension\Core\Type\SubmitType',
    'text' => 'Symfony\Component\Form\Extension\Core\Type\TextType',
    'textarea' => 'Symfony\Component\Form\Extension\Core\Type\TextareaType',
    'time' => 'Symfony\Component\Form\Extension\Core\Type\TimeType',
    'timezone' => 'Symfony\Component\Form\Extension\Core\Type\TimezoneType',
    'url' => 'Symfony\Component\Form\Extension\Core\Type\UrlType',
];

$stats = [
    'string types' => 0,
    'property => choice_label' => 0,
    'collection entry_type' => 0,
    'setDefaultOptions' => 0,
    'getName' => 0,
    'return types' => 0,
    'files touched' => 0,
];

/** Collects the FQCNs a file needs importing, then writes them into the use block. */
function addImports(string $code, array $fqcns): string
{
    $fqcns = array_unique($fqcns);
    $needed = [];

    foreach ($fqcns as $fqcn) {
        if (!preg_match('/^use\s+' . preg_quote($fqcn, '/') . '\s*;/m', $code)) {
            $short = substr($fqcn, strrpos($fqcn, '\\') + 1);
            // Do not shadow a different class already imported under that name.
            if (preg_match('/^use\s+([\w\\\\]+\\\\)?' . preg_quote($short, '/') . '\s*;/m', $code)) {
                continue;
            }
            $needed[] = $fqcn;
        }
    }

    if ($needed === []) {
        return $code;
    }

    sort($needed);
    $block = implode("\n", array_map(static fn (string $f): string => "use {$f};", $needed));

    if (preg_match('/^use\s+[^;]+;\s*$/m', $code, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1];

        return substr($code, 0, $pos) . $block . "\n" . substr($code, $pos);
    }

    // No use block yet: put one after the namespace declaration.
    return preg_replace('/^(namespace\s+[^;]+;\s*\n)/m', "$1\n" . $block . "\n", $code, 1);
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);
    $original = $code;
    $imports = [];

    // ->add('field', 'text', ...)  ->  ->add('field', TextType::class, ...)
    $code = preg_replace_callback(
        '/->add\(\s*(\'[^\']*\'|"[^"]*"|\$[\w>\-\[\]\'"]+)\s*,\s*\'([a-z_]+)\'/',
        static function (array $m) use (&$imports, &$stats): string {
            if (!isset(TYPE_MAP[$m[2]])) {
                return $m[0];
            }
            $fqcn = TYPE_MAP[$m[2]];
            $imports[] = $fqcn;
            ++$stats['string types'];

            return '->add(' . $m[1] . ', ' . substr($fqcn, strrpos($fqcn, '\\') + 1) . '::class';
        },
        $code,
    );

    // 'type' => 'password' inside RepeatedType / CollectionType option arrays.
    $code = preg_replace_callback(
        '/([\'"])(type|entry_type)\1\s*=>\s*\'([a-z_]+)\'/',
        static function (array $m) use (&$imports, &$stats): string {
            if (!isset(TYPE_MAP[$m[3]])) {
                return $m[0];
            }
            $fqcn = TYPE_MAP[$m[3]];
            $imports[] = $fqcn;
            ++$stats['string types'];

            return "'{$m[2]}' => " . substr($fqcn, strrpos($fqcn, '\\') + 1) . '::class';
        },
        $code,
    );

    // Entity/choice `property` was renamed `choice_label` in Symfony 3.
    $n = 0;
    $code = preg_replace('/([\'"])property\1(\s*=>)/', "'choice_label'$2", $code, -1, $n);
    $stats['property => choice_label'] += $n;

    // CollectionType's `type`/`options` became `entry_type`/`entry_options`.
    $code = preg_replace_callback(
        '/->add\(\s*([^,]+),\s*CollectionType::class\s*,\s*(\[|array\()(.*?)(\]|\))\s*\)/s',
        static function (array $m) use (&$stats): string {
            $options = $m[3];
            $count = 0;
            $options = preg_replace('/([\'"])type\1(\s*=>)/', "'entry_type'$2", $options, -1, $c1);
            $options = preg_replace('/([\'"])options\1(\s*=>)/', "'entry_options'$2", $options, -1, $c2);
            $count = $c1 + $c2;
            $stats['collection entry_type'] += $count;

            return '->add(' . $m[1] . ', CollectionType::class, ' . $m[2] . $options . $m[4] . ')';
        },
        $code,
    );

    // setDefaultOptions(OptionsResolverInterface) -> configureOptions(OptionsResolver)
    if (str_contains($code, 'function setDefaultOptions')) {
        $code = preg_replace(
            '/public function setDefaultOptions\(\s*OptionsResolverInterface\s+(\$\w+)\s*\)(\s*:\s*void)?/',
            'public function configureOptions(OptionsResolver $1): void',
            $code,
            -1,
            $n,
        );
        $stats['setDefaultOptions'] += $n;
        $code = str_replace(
            'use Symfony\Component\OptionsResolver\OptionsResolverInterface;',
            'use Symfony\Component\OptionsResolver\OptionsResolver;',
            $code,
        );
        $imports[] = 'Symfony\Component\OptionsResolver\OptionsResolver';
    }

    // A form type's getName() is getBlockPrefix() in Symfony 3+.
    if (preg_match('/class\s+\w+\s+extends\s+(AbstractType|\w*FormType)\b/', $code)) {
        $code = preg_replace(
            '/public function getName\(\)(\s*:\s*string)?\s*\n(\s*)\{/',
            "public function getBlockPrefix(): string\n$2{",
            $code,
            -1,
            $n,
        );
        $stats['getName'] += $n;

        // Symfony 8's AbstractType declares void returns; an override without
        // one is a fatal incompatible-signature error.
        foreach (['buildForm', 'buildView', 'finishView'] as $method) {
            $code = preg_replace(
                '/public function ' . $method . '\(([^)]*)\)\s*\n(\s*)\{/',
                "public function {$method}($1): void\n$2{",
                $code,
                -1,
                $n,
            );
            $stats['return types'] += $n;
        }

        $code = preg_replace(
            '/public function configureOptions\(\s*OptionsResolver\s+(\$\w+)\s*\)\s*\n(\s*)\{/',
            "public function configureOptions(OptionsResolver $1): void\n$2{",
            $code,
            -1,
            $n,
        );
        $stats['return types'] += $n;
    }

    $code = addImports($code, $imports);

    if ($code !== $original) {
        ++$stats['files touched'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

foreach ($stats as $label => $count) {
    printf("%-26s %d\n", $label, $count);
}

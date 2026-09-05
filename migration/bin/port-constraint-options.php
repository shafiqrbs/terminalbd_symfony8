<?php

declare(strict_types=1);

/**
 * Symfony 7 removed the array-of-options constructor for validator constraints.
 *
 *     new NotBlank(array('message' => 'Please input required'))
 *     new NotBlank(message: 'Please input required')
 *
 * Rector converts most of these, but not the ones inside the form types it is
 * told to skip (the files mark-manual-forms.php lists, where its form rule
 * crashes). What is left throws InvalidArgumentException the moment the form is
 * built, which no static check sees.
 *
 * Only simple key => scalar options are rewritten; anything else is reported.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-constraint-options.php [--dry-run]
 */

/**
 * Validator constraints only. `new JsonResponse([...])` takes an array that is
 * data, not options - converting its keys to named arguments would be silent
 * corruption, and there are 61 of those in the tree.
 */
const CONSTRAINTS = [
    'NotBlank', 'NotNull', 'Length', 'Email', 'Range', 'Regex', 'Choice',
    'Count', 'Type', 'File', 'Image', 'Url', 'Date', 'DateTime', 'Time',
    'GreaterThan', 'GreaterThanOrEqual', 'LessThan', 'LessThanOrEqual',
    'EqualTo', 'NotEqualTo', 'Positive', 'PositiveOrZero', 'Negative',
    'Ip', 'Uuid', 'Json', 'Currency', 'Country', 'Language', 'Locale',
    'CardScheme', 'Iban', 'Isbn', 'Issn', 'UserPassword', 'Callback',
];

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

$stats = ['converted' => 0, 'files' => 0, 'skipped' => 0];
$skipped = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!preg_match('/new\s+\w+\(\s*(array\(|\[)/', $code)) {
        continue;
    }

    $original = $code;

    $code = preg_replace_callback(
        '/new\s+(\w+)\(\s*(?:array\(|\[)([^()\[\]]*?)(?:\)|\])\s*\)/',
        static function (array $m) use (&$stats, &$skipped, $path, $root): string {
            [$whole, $class, $options] = $m;

            if (!in_array($class, CONSTRAINTS, true) || trim($options) === '') {
                return $whole;
            }

            $named = [];

            foreach (explode(',', $options) as $pair) {
                if (trim($pair) === '') {
                    continue;
                }

                if (!preg_match('/^\s*[\'"](\w+)[\'"]\s*=>\s*(.+?)\s*$/s', $pair, $pm)) {
                    $stats['skipped']++;
                    $skipped[] = substr($path, strlen($root) + 1) . ': ' . trim($whole);

                    return $whole;
                }

                $named[] = $pm[1] . ': ' . trim($pm[2]);
            }

            if ($named === []) {
                return $whole;
            }

            ++$stats['converted'];

            return 'new ' . $class . '(' . implode(', ', $named) . ')';
        },
        $code,
    );

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf("array-option constructors converted to named arguments: %d in %d files\n",
    $stats['converted'], $stats['files']);

foreach (array_slice(array_unique($skipped), 0, 10) as $line) {
    printf("  not simple enough to convert: %s\n", $line);
}

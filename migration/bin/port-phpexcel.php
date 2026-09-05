<?php

declare(strict_types=1);

/**
 * PHPExcel + liuggio/excelbundle -> PhpSpreadsheet.
 *
 * Both packages are abandoned and neither has a Symfony 5+ release.
 * PhpSpreadsheet kept the worksheet API, so only the type names change; the
 * `phpexcel` service is replaced by App\Tool\Service\SpreadsheetFactory, which
 * deliberately keeps liuggio's method names.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-phpexcel.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

$replacements = [
    'use PHPExcel;' => 'use PhpOffice\PhpSpreadsheet\Spreadsheet;',
    'use Liuggio\ExcelBundle\Factory;' => 'use App\Tool\Service\SpreadsheetFactory as Factory;',
    'PHPExcel $' => 'Spreadsheet $',
    '\PHPExcel_Exception' => '\PhpOffice\PhpSpreadsheet\Exception',
    'PHPExcel_Exception' => '\PhpOffice\PhpSpreadsheet\Exception',
    '@param \PHPExcel ' => '@param \PhpOffice\PhpSpreadsheet\Spreadsheet ',
    '@param \\\\PHPExcel ' => '@param \PhpOffice\PhpSpreadsheet\Spreadsheet ',
];

$stats = ['files' => 0, 'replacements' => 0];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, 'PHPExcel') && !str_contains($code, 'Liuggio')) {
        continue;
    }

    $original = $code;

    foreach ($replacements as $from => $to) {
        $count = 0;
        $code = str_replace($from, $to, $code, $count);
        $stats['replacements'] += $count;
    }

    // Anything left is a bare docblock mention.
    $code = preg_replace('/\bPHPExcel\b(?!_)/', 'Spreadsheet', $code, -1, $n);
    $stats['replacements'] += $n;

    // A bare "\Spreadsheet" left by a fully-qualified \PHPExcel docblock -
    // but never inside an existing FQCN such as PhpSpreadsheet\Spreadsheet.
    $code = preg_replace('/(?<![A-Za-z])\\Spreadsheet\b/', 'Spreadsheet', $code);
    $code = str_replace('$objSpreadsheet', '$objPHPExcel', $code);

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf("files rewritten:  %d\nreplacements:     %d\n", $stats['files'], $stats['replacements']);

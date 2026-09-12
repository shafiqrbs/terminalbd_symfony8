<?php

declare(strict_types=1);

namespace App\Tool\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheetSpreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Replaces the `phpexcel` service (liuggio/excelbundle over Spreadsheet, both
 * abandoned) with PhpSpreadsheet.
 *
 * The method names are liuggio's on purpose: the 15 importers and the one
 * export controller call createPHPExcelObject()/createWriter() and did not
 * have to change. PhpSpreadsheet kept Spreadsheet's worksheet API
 * (setActiveSheetIndex, getHighestRow, rangeToArray, setCellValue), so the
 * bodies of those readers are untouched too.
 */
class SpreadsheetFactory
{
    /** Legacy Spreadsheet writer names -> PhpSpreadsheet ones. */
    private const WRITERS = [
        'Excel2007' => 'Xlsx',
        'Excel5' => 'Xls',
        'CSV' => 'Csv',
        'HTML' => 'Html',
        'PDF' => 'Mpdf',
    ];

    public function createPHPExcelObject(?string $filename = null): Spreadsheet
    {
        return $filename === null ? new Spreadsheet() : IOFactory::load($filename);
    }

    public function createWriter(Spreadsheet $Spreadsheet, string $type = 'Xlsx'): IWriter
    {
        return IOFactory::createWriter($Spreadsheet, self::WRITERS[$type] ?? $type);
    }

    public function createStreamedResponse(IWriter $writer, int $status = 200, array $headers = []): StreamedResponse
    {
        return new StreamedResponse(
            static fn () => $writer->save('php://output'),
            $status,
            $headers,
        );
    }
}

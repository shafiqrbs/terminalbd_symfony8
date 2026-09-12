<?php
/**
 * @Author: Roni Kumar Saha
 * Date: 2/12/16
 * Time: 3:43 PM
 */

namespace App\Tool\Importer;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Doctrine\Persistence\ManagerRegistry;
use App\Tool\Service\SpreadsheetFactory as Factory;

class ExcelDataReader
{
    protected ?ManagerRegistry $doctrine = null;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }


    protected $inventoryConfig;

    /**
     * @var Factory
     */
    private $excelFactory;

    public function __construct(Factory $excelFactory) {
        $this->excelFactory = $excelFactory;
    }

    public function getData($file) {

        $objPHPExcel = $this->excelFactory->createPHPExcelObject($file);

        return $this->getNamedArray($objPHPExcel);
    }

    /**
     * @param \Spreadsheet $objPHPExcel
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @internal param $file
     *
     */
    protected function getNamedArray(Spreadsheet $objPHPExcel)
    {
        $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();

        $headingsArray = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', NULL, TRUE, TRUE, TRUE);
        $headingsArray = $headingsArray[1];

        $namedDataArray = [];

        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, TRUE, TRUE);

            if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {

                $dataArray = [];

                foreach ($headingsArray as $columnKey => $columnHeading) {
                    $dataArray[$columnHeading] = $dataRow[$row][$columnKey];
                }
                $key = $dataArray['Company'].$dataArray['BrandName']. $dataArray['GenericName'].$dataArray['Strength'].$dataArray['Dosage'].$dataArray['UseFor'].$dataArray['DAR'];
                $namedDataArray[$key] = $dataArray;

            }

        }
        return $namedDataArray;
    }

    /**
     * @return Registry
     */
    private function getDoctrain()
    {
        return $this->doctrine;
    }




}
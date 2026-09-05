<?php
/**
 * @Author: Roni Kumar Saha
 * Date: 2/12/16
 * Time: 3:43 PM
 */

namespace App\Inventory\Importer;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Inventory\Repository\PurchaseRepository;
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

                $key = $dataArray['ProductName'].$dataArray['Vendor']. $dataArray['PurchasePrice'].$dataArray['Color'].$dataArray['Size'].$dataArray['Memo'];

                if (empty($dataArray['Quantity'])) {
                    $dataArray['Quantity'] = 1;
                }

                if(isset($namedDataArray[$key])) {
                    $dataArray['Quantity'] = (int)$dataArray['Quantity'] + $namedDataArray[$key]['Quantity'];
                }

                $namedDataArray[$key] = $dataArray;

            }


        }

        return $namedDataArray;
    }

    /**
     * @return PurchaseRepository
     */
    private function getPurchaseRepository()
    {
        $purchaseRepository = $this->getDoctrain()->getRepository(\App\Inventory\Entity\Purchase::class);

        return $purchaseRepository;
    }

    /**
     * @return Registry
     */
    private function getDoctrain()
    {
        return $this->doctrine;
    }




}
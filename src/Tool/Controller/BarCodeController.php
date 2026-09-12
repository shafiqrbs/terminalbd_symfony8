<?php
/**
 * Created by PhpStorm.
 * User: rbs
 * Date: 12/24/15
 * Time: 11:29 AM
 */

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;



/**
 * BarCode controller.
 *
 */
class BarCodeController extends AbstractController
{
    /**
     * Display code as a png image
     */
    public function barcodeAction()
    {

        $barcode = new BarcodeGenerator();
        $barcode->setText('12345678');
        $barcode->setType(BarcodeGenerator::Code128);
        $barcode->setScale(1);
        $barcode->setThickness(20);
        $barcode->setFontSize(10);
        $code = $barcode->generate();
        echo '<img src="data:image/png;base64,'.$code.'" />';

        exit;

    }


}
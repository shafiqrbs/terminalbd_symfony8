<?php

namespace App\Accounting\Controller;

use App\Tool\Service\ToolManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Accounting\Entity\AccountPurchase;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use App\Accounting\Entity\AccountSales;



/**
 * AccountSales controller.
 *
 */
class AccountPrintController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ToolManager $toolManager,
    ) {
    }



	public function getBarcode($value)
	{
		$barcode = new BarcodeGenerator();
		$barcode->setText($value);
		$barcode->setType(BarcodeGenerator::Code39Extended);
		$barcode->setScale(1);
		$barcode->setThickness(25);
		$barcode->setFontSize(8);
		$code = $barcode->generate();
		$data = '';
		$data .= '<img src="data:image/png;base64,'.$code .'" />';
		return $data;
	}

	public function salesPrintAction(AccountSales $sales)
    {
    	$em = $this->em;
	    $template = $sales->getGlobalOption()->getSlug();
	    $result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($sales->getGlobalOption(), $data = ['mobile'=> $sales->getCustomer()->getMobile()]);
	    $balance = empty($result) ? 0 :$result[0]['customerBalance'];
	    $amountInWords = $this->toolManager->intToWords($sales->getAmount());
	    $barcode = $this->getBarcode($sales->getAccountRefNo());
	    return  $this->render("AccountingBundle:Print/sales:print.html.twig",
		    [
			    'amountInWords' => $amountInWords,
			    'barcode' => $barcode,
			    'entity' => $sales,
			    'balance' => $balance,
			    'print' => 'print',
		    ]
	    );
    }

	public function purchasePrintAction(AccountPurchase $sales)
	{
		$em = $this->em;
		$template = $sales->getGlobalOption()->getSlug();
		$result = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorBusinessOutstanding($sales->getGlobalOption(), $data = ['mobile'=> $sales->getCustomer()->getMobile()]);
		$balance = empty($result) ? 0 :$result[0]['customerBalance'];
		$amountInWords = $this->toolManager->intToWords($sales->getPayment());
		$barcode = $this->getBarcode($sales->getAccountRefNo());
		return  $this->render("AccountingBundle:Print/purchase:print.html.twig",
			[
				'amountInWords' => $amountInWords,
				'barcode' => $barcode,
				'entity' => $sales,
				'balance' => $balance,
				'print' => 'print',
			]
		);
	}


}

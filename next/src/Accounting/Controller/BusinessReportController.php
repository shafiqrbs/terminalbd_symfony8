<?php

namespace App\Accounting\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Knp\Snappy\Pdf;

class BusinessReportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }



	public function balanceSheetAction()
	{

		$em = $this->em;
		$data = $_REQUEST;
		$overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportBusinessMonthlyIncome($this->getUser(),$data);
		$accountHeads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent'=>1],['sorting'=>'ASC']);
		$accountHeads = $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->findBy(['isParent'=>1],['sorting'=>'ASC']);
		return $this->render('Accounting/Report/Business/balanceSheet.html.twig', [
			'overview' => $overview,
			'accountHeads' => $accountHeads,
			'searchForm' => $data,
		]);
	}


	public function balanceAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportHmsIncome($globalOption,$data);
        return $this->render('Accounting/Report/incomePdf.html.twig', [
            'overview' => $overview,
            'searchForm' => $data,
        ]);
    }

    public function incomeAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportBusinessIncome($user,$data);
        return $this->render('Accounting/Report/Business/income.html.twig', [
            'overview' => $overview,
            'searchForm' => $data,
        ]);
    }

    public function pdfIncomeAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportBusinessIncome($globalOption,$data);
        $html = $this->renderView(
            'Accounting/Report/Hms/incomePdf.html.twig', [
                'overview' => $overview,
                'print' => ''
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="incomePdf.pdf"');
        echo $pdf;

        return new Response('');
    }

    public function printIncomeAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportBusinessIncome($globalOption,$data);
        return $this->render('Accounting/Report/Hms/incomePdf.html.twig', [
            'overview' => $overview,
            'print' => '<script>window.print();</script>'
        ]);

    }

    public function monthlyIncomeAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        if(!empty($data['startMonth']) and !empty($data['endMonth'])){
            $sm = "01-{$data['startMonth']}-{$data['year']}";
            $compareTo = new DateTime($sm);
            $startMonth =  $compareTo->format('F');
            $endm = "01-{$data['endMonth']}-{$data['year']}";
            $compareTo = new DateTime($endm);
            $endMonth =  $compareTo->format('F,Y');
            $dateRange = $startMonth .' To '.$endMonth;
        }else{
            $compareTo = new DateTime("now");
            $endMonth =  $compareTo->format('F,Y');
            $dateRange =  $compareTo->format('F,Y');
        }

        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMedicineMonthlyIncome($this->getUser(),$data);

        if(!empty($data['startMonth']) and !empty($data['endMonth'])){
            $sm = "01-{$data['startMonth']}-{$data['year']}";
            $compareTo = new DateTime($sm);
            $startMonth =  $compareTo->format('F');
            $endm = "01-{$data['endMonth']}-{$data['year']}";
            $compareTo = new DateTime($endm);
            $endMonth =  $compareTo->format('F,Y');
            $dateRange = $startMonth .' To '.$endMonth;
        }else{
            $compareTo = new DateTime("now");
            $dateRange =  $compareTo->format('F,Y');
        }
        if(empty($data['pdf'])){
            return $this->render('Accounting/Report/Medicine/monthlyIncome.html.twig', [
                'overview' => $overview,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Accounting/Report/Medicine/monthlyIncomePdf.html.twig', [
                    'overview' => $overview,
                    'globalOption' => $this->getUser()->getGlobalOption(),
                    'dateRange' => $dateRange,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'monthlyIncomePdf.pdf');
        }
    }




}

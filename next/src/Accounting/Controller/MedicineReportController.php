<?php

namespace App\Accounting\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Knp\Snappy\Pdf;

class MedicineReportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function balanceAction()
    {

    }

    public function incomeAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMedicineIncome($user,$data);
        if(empty($data['pdf'])){
            return $this->render('AccountingBundle:Report/Medicine:income.html.twig', [
                'overview' => $overview,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'AccountingBundle:Report/Medicine:incomePdf.html.twig', [
                    'overview' => $overview,
                    'globalOption' => $this->getUser()->getGlobalOption(),
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'income.pdf');
        }
    }

    public function downloadPdf($html,$fileName = '')
    {
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);

        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename='{$fileName}'");
        echo $pdf;
        return new Response('');
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
            return $this->render('AccountingBundle:Report/Medicine:monthlyIncome.html.twig', [
                'overview' => $overview,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'AccountingBundle:Report/Medicine:monthlyIncomePdf.html.twig', [
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

<?php

namespace App\DoctorPrescription\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDpsConfig();
        $salesTotalTransactionOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->transactionOverview($config,$data);
        return $this->render('DoctorPrescription/Default/index.html.twig', [
            'salesOverview' => $salesTotalTransactionOverview,
            'previousSalesTransactionOverview' => '',
            'assignDoctors' => '',
            'searchForm' => $data,
        ]);
    }

    public function pdfTodaySalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDpsConfig();
        $salesTotalTransactionOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->transactionOverview($config,$data);
        $html = $this->renderView(
            'DoctorPrescription/Default/today-sales-overview.html.twig', [
                'salesOverview' => $salesTotalTransactionOverview,
                'previousSalesTransactionOverview' => '',
                'assignDoctors' => '',
                'searchForm' => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='sales-overview-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
    }




}

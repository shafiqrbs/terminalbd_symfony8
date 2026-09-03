<?php

namespace App\Restaurant\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice controller.
 *
 */
class ReportController extends AbstractController
{

    public function paginate($entities)
    {

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function salesSummaryAction()
    {

        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $salesTotalTransactionOverview = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->todaySalesOverview($user,$data);
        $salesTransactionOverview = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->todaySalesOverview($user,$data,'true');
        $previousSalesTransactionOverview = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->todaySalesOverview($user,$data,'false');
        $transactionOverview = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->transactionBaseOverview($user,$data);
        $userSalesOverview = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->userSalesOverview($user,$data);
        $categoryOverview = $em->getRepository(\App\Restaurant\Entity\InvoiceParticular::class)->findWithCategoryOverview($user,$data);
        $productGroupOverview = $em->getRepository(\App\Restaurant\Entity\InvoiceParticular::class)->findWithProductGroupOverview($user,$data);
        $productOverview = $em->getRepository(\App\Restaurant\Entity\InvoiceParticular::class)->findWithProductOverview($user,$data);


        if(empty($data['pdf'])){

            return $this->render('Restaurant/Report/salesSummary.html.twig', [

                'salesTotalTransactionOverview'      => $salesTotalTransactionOverview,
                'salesTransactionOverview'      => $salesTransactionOverview,
                'previousSalesTransactionOverview' => $previousSalesTransactionOverview,
                'transactionOverview' => $transactionOverview,
                'userSalesOverview'            => $userSalesOverview,
                'categoryOverview'            => $categoryOverview,
                'productOverview'             => $productOverview,
                'productGroupOverview'             => $productGroupOverview,
                'searchForm'                    => $data,

            ]);

        }else{

            $html = $this->renderView(
                'Restaurant/Report/salesSummaryPdf.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'salesTotalTransactionOverview'      => $salesTotalTransactionOverview,
                    'salesTransactionOverview'      => $salesTransactionOverview,
                    'previousSalesTransactionOverview' => $previousSalesTransactionOverview,
                    'transactionOverview' => $transactionOverview,
                    'categoryOverview'            => $categoryOverview,
                    'productOverview'             => $productOverview,
                    'productGroupOverview'             => $productGroupOverview,
                    'searchForm'                    => $data,
                ]
            );
            $this->downloadPdf($html,'sales-summary.pdf');
        }

    }
    public function userBaseSalesSummaryAction()
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $userSalesOverview = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->userSalesOverview($user,$data);

        if(empty($data['pdf'])){

            return $this->render('Restaurant/Report/userSummary.html.twig', [
                'userSalesOverview'          => $userSalesOverview,
                'searchForm'            => $data,
            ]);

        }else{

            $html = $this->renderView(
                'Restaurant/Report/userSummaryPdf.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'userSalesOverview'          => $userSalesOverview,
                    'searchForm'            => $data,
                ]
            );
            $this->downloadPdf($html,'user-sales-summary.pdf');
        }

    }

    public function categoryBaseSalesSummaryAction()
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $categoryOverview = $em->getRepository(\App\Restaurant\Entity\InvoiceParticular::class)->findWithCategoryOverview($user,$data);
        if(empty($data['pdf'])){
            return $this->render('Restaurant/Report/categorySummary.html.twig', [
                'categoryOverview'          => $categoryOverview,
                'searchForm'            => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Restaurant/Report/categorySummaryPdf.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'categoryOverview'          => $categoryOverview,
                    'searchForm'            => $data,
                ]
            );
            $this->downloadPdf($html,'category-sales-summary.pdf');
        }

    }

    public function productGroupBaseSalesSummaryAction()
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $categoryOverview = $em->getRepository(\App\Restaurant\Entity\InvoiceParticular::class)->findWithProductGroupOverview($user,$data);
        if(empty($data['pdf'])){
            return $this->render('Restaurant/Report/ProductGroupSummaryPdf.html.twig', [
                'categoryOverview'          => $categoryOverview,
                'searchForm'            => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Restaurant/Report/ProductGroupSummaryPdf.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'categoryOverview'          => $categoryOverview,
                    'searchForm'            => $data,
                ]
            );
            $this->downloadPdf($html,'group-sales-summary.pdf');
        }

    }

    public function productBaseSalesSummaryAction()
    {

        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $productOverview = $em->getRepository(\App\Restaurant\Entity\InvoiceParticular::class)->findWithProductOverview($user,$data);
        if(empty($data['pdf'])){
            return $this->render('Restaurant/Report/productSummary.html.twig', [
                'productOverview'          => $productOverview,
                'searchForm'            => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Restaurant/Report/productSummaryPdf.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'productOverview'          => $productOverview,
                    'searchForm'            => $data,
                ]
            );
            $this->downloadPdf($html,'product-sales-summary.pdf');
        }

    }

    public function salesDetailsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->salesReport($user, $data);
        $pagination = $this->paginate($entities);
        $purchaseSalesPrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesItemPurchaseSalesOverview($user, $data);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
        $banks = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(['globalOption' => $user->getGlobalOption(),'status' => 1], ['name' => 'ASC']);
        $mobiles =  $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(['globalOption' => $user->getGlobalOption() , 'status' => 1], ['name' => 'ASC']);

        $cashOverview = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesOverview($user, $data);
        return $this->render('Restaurant/Report/sales/sales.html.twig', [
            'option' => $user->getGlobalOption(),
            'entities' => $pagination,
            'banks' => $banks,
            'mobiles' => $mobiles,
            'cashOverview' => $cashOverview,
            'purchaseSalesPrice' => $purchaseSalesPrice,
            'transactionMethods' => $transactionMethods,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm' => $data,
        ]);
    }

    public function downloadPdf($html,$fileName = '')
    {
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename={$fileName}");
        echo $pdf;
        return new Response('');
    }

}


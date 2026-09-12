<?php

namespace App\Inventory\Controller;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Inventory\Entity\Item;
use Knp\Snappy\Pdf;


/**
 * ItemColor controller.
 *
 */
class BranchReportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }



    public function paginate($entities)
    {
        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all StockItem entities.
     *
     */
    public function overviewAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $branch = $this->getUser()->getProfile()->getBranches();

        $stockOverview = $em->getRepository(\App\Inventory\Entity\Delivery::class)->getStockOverview($inventory,$branch,$data);
        $salesOngoingOverview = $em->getRepository(\App\Inventory\Entity\Delivery::class)->getSalesOngoingOverview($inventory,$branch,$data);
        $salesOverview = $em->getRepository(\App\Inventory\Entity\Delivery::class)->getSalesOverview($inventory,$branch,$data);
        $salesReturnOverview = $em->getRepository(\App\Inventory\Entity\Delivery::class)->getSalesReturnOverview($inventory,$branch,$data);
        $returnOverview = $em->getRepository(\App\Inventory\Entity\Delivery::class)->getReturnOverview($inventory,$branch,$data);

        $stockOverview = ['stock'=>$stockOverview['quantity'] , 'ongoing'=> $salesOngoingOverview['quantity'],'sales' => $salesOverview['quantity'] , 'return'=> $returnOverview['quantity'],'salesReturn' => $salesReturnOverview['quantity']] ;
        return $this->render('Inventory/BranchReport/stockOverview.html.twig', [
            'stockOverview' => $stockOverview
        ]);
    }


    public function stockAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $entities =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockItem( $this->getUser(), $group = 'item',$data);
        $pagination = $this->paginate($entities);
        $stockSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesItem( $this->getUser(), $group = 'item',$data);
        $salesReturnItem = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesReturnItem( $this->getUser(), $group = 'item',$data);
        $stockOngoingItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockOngoingItem( $this->getUser(), $group = 'item',$data);
        $stockReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReturnItem( $this->getUser(), $group = 'item',$data);

        return $this->render('Inventory/BranchReport/stock.html.twig', [
            'entities'                  => $pagination,
            'stockOngoingItem'          => $stockOngoingItem,
            'stockSalesItem'            => $stockSalesItem,
            'salesReturnItem'           => $salesReturnItem,
            'stockReturnItem'           => $stockReturnItem,
            'searchForm'                => $data,
        ]);
    }

     public function stockBarcodeAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $entities =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockItem( $this->getUser(), $group = 'barcode',$data);
        $pagination = $this->paginate($entities);

        $stockSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesItem( $this->getUser(),$group = 'barcode',$data);
        $salesReturnItem = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesReturnItem( $this->getUser(),$group = 'barcode',$data);
        $stockOngoingItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockOngoingItem( $this->getUser(),$group = 'barcode',$data);
        $stockReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReturnItem( $this->getUser(),$group = 'barcode',$data);

        return $this->render('Inventory/BranchReport/barcodeWiseStock.html.twig', [
            'entities'                  => $pagination,
            'stockOngoingItem'          => $stockOngoingItem,
            'stockSalesItem'            => $stockSalesItem,
            'salesReturnItem'           => $salesReturnItem,
            'stockReturnItem'           => $stockReturnItem,
            'searchForm'                => $data,
        ]);
    }

    public function barcodeBranchStockAction()
    {

        $em = $this->em;
        $id = 9699 ;
        $purchaseItem = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($id);
        $user = $this->getUser();
        $branches = $user->getGlobalOption()->getBranches();
        $barcode = '0526024000551';
        $data = ['barcode'=> $barcode];
        $branchWiseReceiveItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseReceiveItem($user , $data);
        var_dump($branchWiseReceiveItem);
        exit;
        $branchWiseSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseSalesItem($user , $purchaseItem);
        $branchWiseSalesReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseSalesReturnItem($user, $purchaseItem);
        $branchWiseOngoingSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseOngoingSalesItem($user, $purchaseItem);
        $ongoingSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->ongoingSalesItem($user, $purchaseItem);
        $branchWiseDeliveryReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseDeliveryReturnItem($user,$purchaseItem);
        return $this->render('Inventory/BranchReport/branchWiseBarcodeProductStock.html.twig', [
            'entity'          => $purchaseItem,
            'branches'          => $branches,
            'branchWiseReceiveItem' => $branchWiseReceiveItem,
            'branchWiseSalesItem' => $branchWiseSalesItem,
            'branchWiseSalesReturnItem' => $branchWiseSalesReturnItem,
            'ongoingSalesItem' => $ongoingSalesItem,
            'branchWiseOngoingSalesItem' => $branchWiseOngoingSalesItem,
            'branchWiseDeliveryReturnItem' => $branchWiseDeliveryReturnItem,
        ]);

    }

    public function stockItemAction()
    {
        return $this->render('Inventory/BranchReport/stockDetails.html.twig', [
            'entity'          => '',
            'entities'      => '',
            'stockOngoingItem' => '',
            'stockSalesItem' => '',
            'stockReturnItem' => '',
        ]);

    }

    public function branchItemDetailsAction(Item $item)
    {
        $em = $this->em;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockItemDetails($user,$item);
        $stockSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesItemDetails($user,$item);
        $stockSalesReturnItem = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesReturnItemDetails($user,$item);
        $stockOngoingItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockOngoingItemDetails($user,$item);
        $stockReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReturnItemDetails($user,$item);

        return $this->render('Inventory/BranchReport/stockDetails.html.twig', [
            'entity'          => $item,
            'entities'      => $entities,
            'stockSalesReturnItem' => $stockSalesReturnItem,
            'stockOngoingItem' => $stockOngoingItem,
            'stockSalesItem' => $stockSalesItem,
            'stockReturnItem' => $stockReturnItem,
        ]);

    }

    public function branchItemSalesAction(Item $item)
    {
        $em = $this->em;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockItemDetails($user,$item);
        $totalQnt = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReceiveSingleItem($user,$item);
        $stockSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesItemDetails($user,$item);
        $stockSalesReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesReturnItemDetails($user,$item);
        $stockOngoingItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockOngoingItemDetails($user,$item);
        $stockReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReturnItemDetails($user,$item);

        return $this->render('Inventory/BranchReport/itemSalesDetails.html.twig', [
            'entity'            => $item,
            'totalQnt'          => $totalQnt,
            'entities'          => $entities,
            'stockReturnSalesItem' => $stockSalesReturnItem,
            'stockOngoingItem'  => $stockOngoingItem,
            'stockSalesItem'    => $stockSalesItem,
            'stockReturnItem'   => $stockReturnItem,
        ]);

    }

    public function branchStockItemDetailsAction(Item $item)
    {
        $em = $this->em;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockItemDetails($user,$item);
        $stockSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesItemDetails($user,$item);
        $stockSalesReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesReturnItemDetails($user,$item);
        $stockOngoingItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockOngoingItemDetails($user,$item);
        $stockReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReturnItemDetails($user,$item);

        return $this->render('Inventory/BranchReport/itemStockDetails.html.twig', [
            'entity'          => $item,
            'entities'      => $entities,
            'stockSalesReturnItem' => $stockSalesReturnItem,
            'stockOngoingItem' => $stockOngoingItem,
            'stockSalesItem' => $stockSalesItem,
            'stockReturnItem' => $stockReturnItem,
        ]);

    }

    public function branchSalesItemAction()
    {
        return $this->render('Inventory/BranchReport/salesItem.html.twig', [
            'entity'            => '',
            'entities'          => '',
            'stockOngoingItem'  => '',
            'stockSalesItem'    => '',
        ]);

    }


    public function branchSalesItemSearchAction(Item $item )
    {
        $em = $this->em;
        $user = $this->getUser();
        $totalQnt = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReceiveSingleItem($user,$item);
        $entities =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesItemHistory($user,$item);
        $stockSalesReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesReturnItemHistory($user,$item);

        return $this->render('Inventory/BranchReport/salesItemDetails.html.twig', [
            'entity'                            => $item,
            'totalQnt'                          => $totalQnt,
            'entities'                          => $entities,
            'stockSalesReturnItem'              => $stockSalesReturnItem
        ]);

    }

    public function branchSalesItemBarcodeAction($barcode)
    {
        $em = $this->em;
        $user = $this->getUser();
        $purchaseItem = $em->getRepository(\App\Inventory\Entity\Delivery::class)->barcodeWithItem($user, $barcode);
        $totalQnt = $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockReceiveSingleItem($user, $purchaseItem->getItem(),$barcode);
        $entities =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesItemHistory($user,$purchaseItem->getItem(),$barcode);
        $stockSalesReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->stockSalesReturnItemHistory($user,$purchaseItem->getItem(),$barcode);

        return $this->render('Inventory/BranchReport/salesItemDetails.html.twig', [
            'entity'                            =>  $purchaseItem->getItem(),
            'totalQnt'                          => $totalQnt,
            'entities'                          => $entities,
            'stockSalesReturnItem'              => $stockSalesReturnItem
        ]);

    }

    public function branchWiseBarcodeProductStockAction( $id){



        $em = $this->em;
        $purchaseItem = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($id);
        $user = $this->getUser();
        $branches = $user->getGlobalOption()->getBranches();
        $branchWiseReceiveItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseReceiveItem($user , $purchaseItem->getId());
        $branchWiseSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseSalesItem($user , $purchaseItem);
        $branchWiseSalesReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseSalesReturnItem($user, $purchaseItem);
        $branchWiseOngoingSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseOngoingSalesItem($user, $purchaseItem);
        $ongoingSalesItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->ongoingSalesItem($user, $purchaseItem);
        $branchWiseDeliveryReturnItem =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->branchWiseDeliveryReturnItem($user,$purchaseItem);
        return $this->render('Inventory/BranchReport/branchWiseBarcodeProductStock.html.twig', [
            'entity'          => $purchaseItem,
            'branches'          => $branches,
            'branchWiseReceiveItem' => $branchWiseReceiveItem,
            'branchWiseSalesItem' => $branchWiseSalesItem,
            'branchWiseSalesReturnItem' => $branchWiseSalesReturnItem,
            'ongoingSalesItem' => $ongoingSalesItem,
            'branchWiseOngoingSalesItem' => $branchWiseOngoingSalesItem,
            'branchWiseDeliveryReturnItem' => $branchWiseDeliveryReturnItem,
        ]);

    }



    public function pdfIncomeAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportIncome($globalOption,$data);
        $html = $this->renderView(
            'Accounting/Report/incomePdf.html.twig', [
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

    }

    public function printIncomeAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportIncome($globalOption,$data);
        return $this->render('Accounting/Report/incomePdf.html.twig', [
            'overview' => $overview,
            'print' => '<script>window.print();</script>'
        ]);

    }






}

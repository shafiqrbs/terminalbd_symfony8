<?php

namespace App\Inventory\Controller;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;


/**
 * ItemColor controller.
 *
 */
class ReportController extends AbstractController
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
      //  $purchaseOverview = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findItemWithPurchaseQuantity($inventory);
      //  var_dump($purchaseOverview);
     //   exit;

        $purchaseOverview = $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseOverview($inventory,$data);
        $priceOverview = $em->getRepository(\App\Inventory\Entity\Item::class)->getStockPriceOverview($inventory,$data);
        $salesPurchasePrice = $em->getRepository(\App\Inventory\Entity\SalesItem::class)->reportPurchasePrice($this->getUser(),$data);
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        return $this->render('Inventory/Report/stockOverview.html.twig', [
            'priceOverview' => $priceOverview[0],
            'stockOverview' => $stockOverview,
            'purchaseOverview' => $purchaseOverview,
            'salesPurchasePrice' => $salesPurchasePrice,
        ]);
    }



    public function tillStockAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        $entities = $em->getRepository(\App\Inventory\Entity\StockItem::class)->stockItem($inventory,$data);
        $pagination = $this->paginate($entities);
        $previousQuantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->tillStockItem($mode ='previous',$inventory,$data);
        $purchaseQuantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->tillStockItem($mode ='purchase',$inventory,$data);
        $saleQuantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->tillStockItem($mode ='sales',$inventory,$data);

        return $this->render('Inventory/Report/tillStock.html.twig', [
            'entities' => $pagination,
            'previousQuantity' => $previousQuantity,
            'purchaseQuantity' => $purchaseQuantity,
            'saleQuantity' => $saleQuantity,
            'stockOverview' => $stockOverview,
            'searchForm' => $data,
        ]);
    }

    public function periodicStockAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        $entities = $em->getRepository(\App\Inventory\Entity\StockItem::class)->stockItem($inventory,$data);
        $pagination = $this->paginate($entities);
        $previousQuantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->periodicStockItem($mode ='previous',$inventory,$data);
        $purchaseQuantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->periodicStockItem($mode ='purchase',$inventory,$data);
        $saleQuantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->periodicStockItem($mode ='sales',$inventory,$data);

        return $this->render('Inventory/Report/periodicStock.html.twig', [
            'entities' => $pagination,
            'previousQuantity' => $previousQuantity,
            'purchaseQuantity' => $purchaseQuantity,
            'saleQuantity' => $saleQuantity,
            'stockOverview' => $stockOverview,
            'searchForm' => $data,
        ]);

    }

    public function operationalStockAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\StockItem::class)->stockItem($inventory,$data);
        $pagination = $this->paginate($entities);
        $quantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->operationalStockItem($inventory,$data);
        return $this->render('Inventory/Report/operationalStock.html.twig', [
            'entities' => $pagination,
            'quantity' => $quantity,
            'searchForm' => $data,
        ]);

    }

    public function groupStockAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        //$stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        $entities = $em->getRepository(\App\Inventory\Entity\StockItem::class)->stockGroupItemName($inventory,$data);
        $pagination = $this->paginate($entities);

        $previousQuantity = $em->getRepository(\App\Inventory\Entity\StockItem::class)->groupStockItem($mode ='previous',$inventory,$data);
        $purchase = $em->getRepository(\App\Inventory\Entity\StockItem::class)->groupStockItem($mode ='purchase',$inventory,$data);
        $purchaseReturn = $em->getRepository(\App\Inventory\Entity\StockItem::class)->groupStockItem($mode ='purchaseReturn',$inventory,$data);
        $sale = $em->getRepository(\App\Inventory\Entity\StockItem::class)->groupStockItem($mode ='sales',$inventory,$data);
        $salesReturn = $em->getRepository(\App\Inventory\Entity\StockItem::class)->groupStockItem($mode ='salesReturn',$inventory,$data);
        $damage = $em->getRepository(\App\Inventory\Entity\StockItem::class)->groupStockItem($mode ='damage',$inventory,$data);

        return $this->render('Inventory/Report/productGroupStock.html.twig', [
            'entities' => $pagination,
            'previousQuantity' => $previousQuantity,
            'purchase' => $purchase,
            'purchaseReturn' => $purchaseReturn,
            'sale' => $sale,
            'salesReturn' => $salesReturn,
            'damage' => $damage,
            'searchForm' => $data,
        ]);

    }

    public function stockAction($group)
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getGroupStock($inventory,$group);
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        return $this->render('Inventory/Report/'.$group.'.html.twig', [
            'entities' => $entities,
            'stockOverview' => $stockOverview,
            'searchForm' => $data,
        ]);
    }

    public function stockItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        $entities = $em->getRepository(\App\Inventory\Entity\Item::class)->reportFindWithSearch($inventory,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/Report/stock.html.twig', [
            'entities' => $pagination,
            'stockOverview' => $stockOverview,
            'searchForm' => $data,
        ]);
    }

    public function purchaseOverviewAction()
    {
	    $em = $this->em;
	    $data = $_REQUEST;
	    $user = $this->getUser();
	    $inventory = $user->getGlobalOption()->getInventoryConfig()->getId();
	    $cashOverview = $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseOverview($inventory,$data);
	    $transactionCash = $em->getRepository(\App\Inventory\Entity\Purchase::class)->reportTransactionOverview($inventory,$data);
	    $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
	    return $this->render('Inventory/Report/purchase/overview.html.twig', [
		    'inventory' => $inventory,
		    'cashOverview'              => $cashOverview ,
		    'transactionCash'           => $transactionCash ,
		    'transactionMethods'        => $transactionMethods ,
		    'option'                    => $this->getUser()->getGlobalOption(),
		    'searchForm'                => $data ,
	    ]);
    }


    public function purchaseAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities       = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getProcessStock($inventory,$data);
        $pagination = $this->paginate($entities);
        $purchaseQuantity   = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getGroupPurchaseItemStock($inventory,$data);
        return $this->render('Inventory/Report/purchase.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
            'purchaseQuantity' => $purchaseQuantity,
        ]);
    }

    public function salesTransactionOverviewAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $inventory = $user->getGlobalOption()->getInventoryConfig();
        $cashOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesOverview($user,$data);
        $purchaseSalesPrice = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesItemPurchaseSalesOverview($user,$data);
        $transactionCash = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesTransactionOverview($user,$data);
        $salesMode = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesModeOverview($user,$data);
        $salesProcess = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesProcessOverview($user,$data);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
        return $this->render('Inventory/Report/sales/salesOverview.html.twig', [
            'inventory' => $inventory,
            'cashOverview'              => $cashOverview ,
            'purchaseSalesPrice'        => $purchaseSalesPrice ,
            'transactionCash'           => $transactionCash ,
            'salesMode'                 => $salesMode ,
            'salesProcess'              => $salesProcess ,
            'transactionMethods'        => $transactionMethods ,
            'branches'                  => $this->getUser()->getGlobalOption()->getBranches(),
            'option'                    => $this->getUser()->getGlobalOption(),
            'searchForm'                => $data ,
        ]);
    }

    public function salesTransactionAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $inventory = $user->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->salesReport($user,$data);
        $pagination = $this->paginate($entities);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
        $purchaseSalesPrice = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesItemPurchaseSalesOverview($user,$data);
        $cashOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesOverview($user,$data);

        return $this->render('Inventory/Report/sales/sales.html.twig', [
            'option'                    => $this->getUser()->getGlobalOption(),
            'inventory'             => $inventory ,
            'entities'              => $pagination ,
            'cashOverview'          => $cashOverview,
            'purchaseSalesPrice'    => $purchaseSalesPrice,
            'transactionMethods'    => $transactionMethods ,
            'branches'              => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm'            => $data,
        ]);
    }

    public function salesStockItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $inventory = $user->getGlobalOption()->getInventoryConfig();
        $purchaseSalesPrice = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesItemPurchaseSalesOverview($user,$data);
        $cashOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesOverview($user,$data);
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesItem($user,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/Report/sales/salesStock.html.twig', [
            'option'                => $this->getUser()->getGlobalOption(),
            'inventory'             => $inventory,
            'entities'              => $pagination,
            'cashOverview'          => $cashOverview,
            'purchaseSalesItem'     => $purchaseSalesPrice,
            'branches'              => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm'            => $data
        ]);
    }

    public function salesItemDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $inventory = $user->getGlobalOption()->getInventoryConfig();
        if($inventory->getSalesMode() == "stock"){
            $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesItemDetails($user,$data);
        }else{
            $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesItemPurchaseItemDetails($user,$data);
        }
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/Report/sales/salesItemDetails.html.twig', [
            'option'                => $this->getUser()->getGlobalOption(),
            'inventory'             => $inventory,
            'entities'              => $pagination,
            'branches'              => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm'            => $data,
        ]);
    }

    public function dailySalesProfitAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $inventory = $user->getGlobalOption()->getInventoryConfig();
        $sales = $em->getRepository(\App\Inventory\Entity\Sales::class)->inventorySalesDaily($user,$data);
        $salesPurchase = $em->getRepository(\App\Inventory\Entity\SalesItem::class)->inventorySalesDaily($user,$data);
        $expenses = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->monthlyExpenditure($user,$data);
        if(empty($data['download'])){
            return $this->render('Inventory/Report/sales/dailySalesProfit.html.twig', [
                'option'                    => $this->getUser()->getGlobalOption(),
                'monthlySales'              => $sales,
                'monthlySalesPurchase'      => $salesPurchase,
                'expenses'                  => $expenses,
                'searchForm'                => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Inventory/Report/sales/dailySalesProfitPdf.html.twig', [
                    'globalOption'              => $this->getUser()->getGlobalOption(),
                    'monthlySales'              => $sales,
                    'monthlySalesPurchase'      => $salesPurchase,
                    'expenses'                  => $expenses,
                    'searchForm'                => $data,
                ]
            );
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy          = new Pdf($wkhtmltopdfPath);
            $pdf             = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="dailySalesProfitPdf.pdf"');
            echo $pdf;
            return new Response('');
        }

    }

    public function monthlySalesProfitAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $sales = $em->getRepository(\App\Inventory\Entity\Sales::class)->inventorySalesMonthly($user,$data);
        $purchaseEntities = $em->getRepository(\App\Inventory\Entity\SalesItem::class)->inventorySalesMonthly($user,$data);
        $expenses = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->yearlyExpenditure($user,$data);
        if(empty($data['download'])){
            return $this->render('Inventory/Report/sales/monthlySalesProfit.html.twig', [
                'option'                    => $this->getUser()->getGlobalOption(),
                'monthlySales'              => $sales,
                'monthlySalesPurchase'      => $purchaseEntities,
                'expenses'                  => $expenses,
                'searchForm'                => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Inventory/Report/sales/monthlySalesProfitPdf.html.twig', [
                    'globalOption'              => $this->getUser()->getGlobalOption(),
                    'monthlySales'              => $sales,
                    'monthlySalesPurchase'      => $purchaseEntities,
                    'expenses'                  => $expenses,
                    'searchForm'                => $data,
                ]
            );
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy          = new Pdf($wkhtmltopdfPath);
            $pdf             = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="monthlySalesProfitPdf.pdf"');
            echo $pdf;
            return new Response('');

        }

    }


    public function dailySalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->inventorySalesDaily($user,$data);
        return $this->render('Inventory/Report/sales/dailySales.html.twig', [
            'entities'      => $entities ,
            'searchForm'    => $data ,
        ]);
    }

    public function monthlySalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->inventorySalesMonthly($user,$data);
        return $this->render('Inventory/Report/sales/monthlySales.html.twig', [
            'entities'      => $entities ,
            'searchForm'    => $data ,
        ]);
    }

    public function salesUserAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $inventory = $user->getGlobalOption()->getInventoryConfig();
        $salesPurchasePrice = $em->getRepository(\App\Inventory\Entity\Sales::class)->salesUserPurchasePriceReport($user,$data);
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->salesUserReport($user,$data);
        return $this->render('Inventory/Report/sales/salesUser.html.twig', [
            'inventory'      => $inventory ,
            'entities'      => $entities ,
            'salesPurchasePrice'      => $salesPurchasePrice ,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm'    => $data ,
        ]);
    }

    public function monthlyUserSalesAction(){

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $inventory = $user->getGlobalOption()->getInventoryConfig();
        $employees = $em->getRepository(\App\DomainUser\Entity\DomainUser::class)->getSalesUser($user->getGlobalOption());
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->monthlySales($user,$data);
        $salesAmount = [];
        foreach($entities as $row) {
            $salesAmount[$row['salesBy'].$row['month']] = $row['total'];
        }

        return $this->render('Inventory/Report/sales/salesMonthlyUser.html.twig', [
            'inventory'      => $inventory ,
            'salesAmount'      => $salesAmount ,
            'employees'      => $employees ,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm'    => $data ,
        ]);

    }

    public function categoryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

    }

    /**
     * Lists all ItemColor entities.
     *
     */
    public function overviewinAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();

        $entities = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockPriceOverview($inventory,$data);
        return $this->render('Inventory/Report/itemVendor.html.twig', [
            'entities' => $entities,
            'searchForm' => $data
        ]);
    }

    /**
     * Lists all ItemColor entities.
     *
     */
    public function overviAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockPriceOverview($inventory,$data);
        if(!empty($data['group'])){
            $twig = 'stockOverview';
        }else{
            $twig = $data['group'];
        }
        return $this->render('Inventory/Report/'.$twig.'.html.twig', [
            'stockOverview' => $stockOverview,
            'searchForm' => $data
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

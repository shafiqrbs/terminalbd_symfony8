<?php

namespace App\Inventory\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Inventory\Entity\Item;
use Symfony\Component\HttpFoundation\Response;


/**
 * StockItem controller.
 *
 */
class StockItemController extends AbstractController
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


    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\Item::class)->findWithSearch($inventory,$data);
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/StockItem/index.html.twig', [
            'entities' => $pagination,
            'stockOverview' => $stockOverview,
            'searchForm' => $data,
        ]);
    }

    public function itemShortListAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\Item::class)->findWithShortListSearch($inventory,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/StockItem/shortList.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }

    public function barcodeStockAction()
    {

        $em = $this->em;
        $user = $this->getUser();
        $data = $_REQUEST;
        $branchStocks ='';
        $entities = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->getPurchaseItems($user->getGlobalOption(),$data);
        $pagination = $this->paginate($entities);
        if($entities){
            $branchStocks = $em->getRepository(\App\Inventory\Entity\StockItem::class)->barcodeWiseStock($user,$pagination);
        }
        return $this->render('Inventory/StockItem/barcodeStock.html.twig', [
            'branchStocks'          => $branchStocks,
            'pagination'            => $pagination,
            'searchForm'            => $data,
        ]);
    }

    public function indexResultsAction()
    {
        // TODO(migration): AbstractController::get() was removed; inject this service.
        $datatable = $this->get('app.datatable.stockitem');
        $datatable->buildDatatable();

        // TODO(migration): AbstractController::get() was removed; inject this service.
        $query = $this->get('sg_datatables.query')->getQueryFrom($datatable);

        return $query->getResponse();
    }
    /**
     * Creates a new StockItem entity.
     *
     */

    /**
     * Finds and displays a Item entity.
     *
     */
    public function showAction(Item $item)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($item);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        return $this->render('Inventory/StockItem/show.html.twig', [
            'entity'      => $item,
        ]);
    }

    public function barcodeBranchStockAction()
    {

        $em = $this->em;
        $user = $this->getUser();
        $data = $_REQUEST;
        $branchStocks ='';
        $entities = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->getPurchaseItems($user->getGlobalOption(),$data);
        $pagination = $this->paginate($entities);
        if($entities){
            $branchStocks = $em->getRepository(\App\Inventory\Entity\StockItem::class)->barcodeWiseBranchItem($user,$pagination);
        }
        return $this->render('Inventory/StockItem/barcodeWiseStock.html.twig', [
            'branchStocks'          => $branchStocks,
            'pagination'            => $pagination,
            'searchForm'            => $data,
        ]);

    }

    public function singleBarcodeBranchStockAction($barcode)
    {

        $em = $this->em;
        $user = $this->getUser();
        $data = ['barcode'=>$barcode];
        $branchStocks ='';
        $entities = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->getPurchaseItems($user->getGlobalOption(),$data);
        if($entities){
            $purchaseItem = $entities->getArrayResult()[0] ;
            $branchStocks = $em->getRepository(\App\Inventory\Entity\StockItem::class)->singleBarcodeWiseBranchItem($user, $purchaseItem);
        }
        return new Response($branchStocks);
        exit;

    }


}

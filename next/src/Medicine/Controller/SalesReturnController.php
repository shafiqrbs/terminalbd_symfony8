<?php

namespace App\Medicine\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DomainUser\Entity\Customer;
use App\Medicine\Entity\MedicineSales;
use App\Medicine\Entity\MedicineSalesItem;
use App\Medicine\Entity\MedicineSalesReturn;
use App\Medicine\Entity\MedicineSalesReturnInvoice;
use App\Medicine\Form\SalesTemporaryItemType;
use App\Medicine\Form\SalesTemporaryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SalesReturn controller.
 *
 */
class SalesReturnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {
        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }

    /**
     * Lists all SalesReturn entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $data = $_REQUEST;
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesReturn::class)->invoiceLists($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Medicine/SalesReturn/index.html.twig', array(
            'entities' => $pagination,
        ));
    }


     /**
     * Lists all SalesReturn entities.
     *
     */
    public function salesReturnInvoiceAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesReturnInvoice::class)->invoiceLists($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Medicine/SalesReturn/return-invoice.html.twig', array(
            'entities' => $pagination,
        ));
    }


    public function customerItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $pagination = '';
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->salesItemLists($this->getUser(),$data);
        if($entities){
            $pagination = $entities->getResult();
        }
        $customerMobile = (isset($_REQUEST['customer']) and ( $_REQUEST['customer'])) ? $_REQUEST['customer'] : '';
        $customer = "";
        $balance = "";
        $returnItems = "";
        if($customerMobile){
            $customer = $this->em->getRepository(Customer::class)->findOneBy(array('globalOption'=> $this->getUser()->getGlobalOption(),'mobile'=>$customerMobile));
            if($customer){
                $outstanding = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($this->getUser()->getGlobalOption(),$customer->getId());
                $balance = empty($outstanding) ? 0 : $outstanding;
                $returnItems = $this->em->getRepository(MedicineSalesReturn::class)->getCustomerExistReturnItem($customer->getId());
            }

        }
        return $this->render('Medicine/SalesReturn/customer-item.html.twig', array(
            'entities' => $pagination,
            'balance' => $balance,
            'returnItems' => $returnItems,
            'customer' => $customer,
            'discountPercentLists' => '',
            'searchForm' => $data,
        ));
    }

    public function newAction(MedicineSalesItem $item){

        $balance = 0;
	    $user = $this->getUser();
	    $entity = $item->getMedicineSales();
        $result = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesTemporary::class)->getSubTotalAmount($user);
        $discountPercentLists = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->discountPercentList();
        if($entity->getCustomer()){
            $outstanding = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($this->getUser()->getGlobalOption(),$entity->getCustomer());
            $balance = empty($outstanding) ? 0 : $outstanding;
        }
        return $this->render('Medicine/SalesReturn/new.html.twig', array(
            'entity' => $item->getMedicineSales(),
            'user'   => $user,
            'discountPercentLists'   => $discountPercentLists,
            'balance'   => $balance,
            'result'   => $result,
        ));
    }

    public function groupItemNewAction(Request $request){

        $balance = 0;
        $em = $this->em;
        $user = $this->getUser();
        $data = $request->request->all();
        $items = $data['item'];
        $globalOption = $this->getUser()->getGlobalOption();
        $returnInvoice = $this->em->getRepository(MedicineSalesReturnInvoice::class)->insertMedicineSalesReturn($globalOption,$data);
        if($returnInvoice and $returnInvoice != 'invalid'){
            foreach ($items as $item){
                if($data['quantity'][$item] > 0){
                    $qnt = $data['quantity'][$item];
                    /* @var $salesItem MedicineSalesItem */
                    $salesItem = $this->em->getRepository(MedicineSalesItem::class)->find($item);
                    $entity = new MedicineSalesReturn();
                    $entity->setMedicineConfig($globalOption->getMedicineConfig());
                    $entity->setMedicineSalesReturnInvoice($returnInvoice);
                    $entity->setQuantity($qnt);
                    $entity->setMedicineStock($salesItem->getMedicineStock());
                    $entity->setMedicineSalesItem($salesItem);
                    if($salesItem->getMedicinePurchaseItem()){
                        $entity->setMedicinePurchaseItem($salesItem->getMedicinePurchaseItem());
                    }
                    $price = empty($data['price'][$item]) ? $salesItem->getSalesPrice() : $data['price'][$item];
                    $entity->setSalesPrice($price);
                    $entity->setSubTotal($entity->getSalesPrice() * $entity->getQuantity());
                    $em->persist($entity);
                    $em->flush();
                    if($salesItem->getMedicinePurchaseItem()) {
                        $this->em->getRepository( \App\Medicine\Entity\MedicinePurchaseItem::class )->updateRemovePurchaseItemQuantity( $salesItem->getMedicinePurchaseItem(), 'sales-return' );
                    }
                    $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($salesItem->getMedicineStock(), 'sales-return');
                }
            }
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been return successfully"
            );
        }
        return $this->redirect($this->generateUrl('medicine_sales_customer_return_invoice'));
    }

	private function createMedicineSalesItemForm(MedicineSalesItem $salesItem )
	{
        $globalOption = $this->getUser()->getGlobalOption();
        $em = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class);
     // TODO(migration): SF3+ takes a form type class-string, not an instance.
     // Move these constructor arguments into SalesTemporaryItemType::configureOptions() and pass them as form options: $globalOption,$em), $salesItem, array(
	    $form = $this->createForm(new SalesTemporaryItemType($globalOption,$em), $salesItem, array(
			'action' => $this->generateUrl('medicine_sales_temporary_item_add'),
			'method' => 'POST',
			'attr' => array(
				'class' => 'form-horizontal',
				'id' => 'salesTemporaryItemForm',
				'novalidate' => 'novalidate',
			)
		));
		return $form;
	}

	private function createCreateForm(MedicineSales $entity)
	{
		$globalOption = $this->getUser()->getGlobalOption();
		$location = $this->em->getRepository(\App\Location\Entity\Location::class);
  // TODO(migration): SF3+ takes a form type class-string, not an instance.
  // Move these constructor arguments into SalesTemporaryType::configureOptions() and pass them as form options: $globalOption,$location), $entity, array(
		$form = $this->createForm(new SalesTemporaryType($globalOption,$location), $entity, array(
			'action' => $this->generateUrl('medicine_sales_temporary_create'),
			'method' => 'POST',
			'attr' => array(
				'class' => 'form-horizontal',
				'id' => 'salesTemporaryForm',
				'novalidate' => 'novalidate',
			)
		));
		return $form;
	}



	/**
     * Creates a new SalesReturn entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $data = $request->request->all();
        if (isset($data['quantity']) and $data['quantity']) {
            foreach ($data['quantity'] as $key => $qnt) {
            if ($qnt > 0) {
                $adjustment = isset($data['isAdjustment'][$data['salesItem'][$key]]) ? 1 : 0;
                $entity = new MedicineSalesReturn();
                $salesItem = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->find($data['salesItem'][$key]);
                $entity->setMedicineConfig($config);
                $entity->setQuantity($qnt);
                $entity->setAdjustment($adjustment);
                $entity->setMedicineStock($salesItem->getMedicineStock());
                if ($salesItem->getMedicinePurchaseItem()) {
                    $entity->setMedicinePurchaseItem($salesItem->getMedicinePurchaseItem());
                }
                $entity->setMedicineSalesItem($salesItem);
                $price = empty($data['price'][$key]) ? $salesItem->getSalesPrice() : $data['price'][$key];
                $entity->setSalesPrice($price);
                $entity->setSubTotal($entity->getSalesPrice() * $entity->getQuantity());
                $em->persist($entity);
                $em->flush();
                if (!empty($salesItem->getMedicinePurchaseItem())) {
                    $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($salesItem->getMedicinePurchaseItem(), 'sales-return');
                }
                $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($salesItem->getMedicineStock(), 'sales-return');

            }
        }
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been inserted successfully"
        );
        return $this->redirect($this->generateUrl('medicine_sales_return'));

    }

    public function deleteAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineSalesReturn::class)->find($id);
	    $item = $entity->getMedicinePurchaseItem();
        $stock = $entity->getMedicineStock();
	    $this->requestStack->getSession()->set('item', $item);
	    $this->requestStack->getSession()->set('stock', $stock);
	    $em->remove($entity);
        $em->flush();
	    $item = $this->requestStack->getSession()->get('item');
	    $stock = $this->requestStack->getSession()->get('stock');
	    if(!empty($item)) {
		    $this->em->getRepository( \App\Medicine\Entity\MedicinePurchaseItem::class )->updateRemovePurchaseItemQuantity( $item, 'sales-return' );
	    }
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'sales-return');
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('medicine_sales_return'));
    }

    public function approveAction(MedicineSalesReturn $entity)
    {

    	$em = $this->em;
        if (!empty($entity) and $entity->getProcess() !='approved') {
            $em = $this->em;
            if($entity->isAdjustment() == 1){
                $em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertMedicineAccountSalesReturn($entity);
            }else{
                $journal = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertMedicineAccountSalesReturn($entity);
                $entity->setJournal($journal);
            }
            $entity->setProcess('approved');
            $em->flush();
	        return new Response('success');
        } else {
            return new Response('failed');
        }

    }

    public function showInvoiceAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->em;
        $return = $em->getRepository(MedicineSalesReturnInvoice::class)->findOneBy(array('medicineConfig'=>$config,'id'=>$id));
        return $this->render('Medicine/SalesReturn/show.html.twig', array(
            'entity' => $return,
        ));
    }

    public function printInvoiceAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->em;
        $entity = $em->getRepository(MedicineSalesReturnInvoice::class)->findOneBy(array('medicineConfig'=>$config,'id'=>$id));
        $outstanding = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($this->getUser()->getGlobalOption(),$entity->getCustomer());
        $balance = empty($outstanding) ? 0 : $outstanding;
        return $this->render('Medicine/SalesReturn/print.html.twig', array(
            'entity' => $entity,
            'balance' => $balance,
        ));
    }

    public function deleteInvoiceAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->em;
        $return = $em->getRepository(MedicineSalesReturnInvoice::class)->findOneBy(array('medicineConfig'=>$config,'id'=>$id));
        foreach ($return->getMedicineSalesReturns() as $entity){
            $item = $entity->getMedicinePurchaseItem();
            $stock = $entity->getMedicineStock();
            $this->requestStack->getSession()->set('item', $item);
            $this->requestStack->getSession()->set('stock', $stock);
            $em->remove($entity);
            $em->flush();
            $item = $this->requestStack->getSession()->get('item');
            $stock = $this->requestStack->getSession()->get('stock');
            if(!empty($item)) {
                $this->em->getRepository( \App\Medicine\Entity\MedicinePurchaseItem::class )->updateRemovePurchaseItemQuantity( $item, 'sales-return' );
            }
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'sales-return');
        }
        if($return->getProcess() == 'approved'){
            if($return->getAdjustment() > 0){
                $em->getRepository(\App\Accounting\Entity\AccountSales::class)->accountMedicineSalesReturnDelete($return);
            }
            if($return->getPayment() > 0){
                $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->accountMedicineSalesReturnDelete($return);
            }
        }
        $em->remove($return);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('medicine_sales_customer_return_invoice'));
    }

    public function approveInvoiceAction(MedicineSalesReturnInvoice $entity)
    {
        $em = $this->em;
        if (!empty($entity) and $entity->getProcess() !='approved') {
            if($entity->getAdjustment() > 0){
                $em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertMedicineAccountSalesReturnInvoice($entity);
            }
            if($entity->getPayment() > 0){
                $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertMedicineAccountSalesReturnInvoice($entity);
            }
            $entity->setProcess('approved');
            if($entity->getMedicineSalesReturns()) {
                foreach ($entity->getMedicineSalesReturns() as $item) {
                    $item->setProcess('approved');
                }
            }
            $em->flush();
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }

    public function updateTotalAmountMismatchAction(){

        $this->em->getRepository(MedicineSalesReturnInvoice::class)->updateTotalAmountMismatch();
    }


}

<?php

namespace App\Medicine\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicinePurchaseReturn;
use App\Medicine\Entity\MedicinePurchaseItem;
use App\Medicine\Entity\MedicinePurchaseReturnItem;
use App\Medicine\Form\PurchaseReturnItemType;
use App\Medicine\Form\PurchaseReturnType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * PurchaseReturn controller.
 *
 */
class MedicinePurchaseReturnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all PurchaseReturn entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $entity = new MedicinePurchaseReturn();
        $form = $this->createCreateForm($entity);
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->findBy(array('medicineConfig' => $config),array('created'=>'ASC'));
        return $this->render('Medicine/PurchaseReturn/index.html.twig', array(
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function newAction(){

        $entity = new MedicinePurchaseReturn();
        $form = $this->createCreateForm($entity);
        return $this->render('Medicine/PurchaseReturn/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Medicine/PurchaseReturn/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Creates a new PurchaseReturn entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MedicinePurchaseReturn();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $entity->setMedicineConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('medicine_purchase_return_edit', array('id' => $entity->getId())));
        }

        return $this->render('Medicine/PurchaseReturn/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a PurchaseReturn entity.
     *
     * @param MedicinePurchaseReturn $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MedicinePurchaseReturn $entity)
    {
        $global = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into PurchaseReturnType::configureOptions() and pass them as form options: $global), $entity, array(
        $form = $this->createForm(new PurchaseReturnType($global), $entity, array(
            'action' => $this->generateUrl('medicine_purchase_return_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to edit an existing PurchaseReturn entity.
     *
     */
    public function editAction(MedicinePurchaseReturn $entity)
    {
        $em = $this->em;
        $purchaseItem = new MedicinePurchaseReturnItem();
        $form = $this->createEditForm($entity,$purchaseItem);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseReturn entity.');
        }
        return $this->render('Medicine/PurchaseReturn/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
    * Creates a form to edit a PurchaseReturn entity.
    *
    * @param PurchaseReturn $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MedicinePurchaseReturn $entity,MedicinePurchaseReturnItem $purchaseItem)
    {
        $form = $this->createForm(new PurchaseReturnItemType(), $purchaseItem, array(
            'action' => $this->generateUrl('medicine_purchase_return_update', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing PurchaseReturn entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseReturn entity.');
        }
        $data = $request->request->all();
        $purchaseReturnItem = new MedicinePurchaseReturnItem();
        $form = $this->createEditForm($entity,$purchaseReturnItem);
        $form->handleRequest($request);
        if ($form->isValid()) {

            $purchaseReturnItem->setMedicinePurchaseReturn($entity);

            $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($data['medicineStock']);
            $purchaseReturnItem->setMedicineStock($stock);
          //  $purchaseItem = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->find($data['medicinePurchaseItem']);
           // $purchaseReturnItem->setMedicinePurchaseItem($purchaseItem);
            $purchaseReturnItem->setPurchasePrice($stock->getPurchasePrice());
            $purchaseReturnItem->setSubTotal($purchaseReturnItem->getPurchasePrice() * $purchaseReturnItem->getQuantity());
            $em->persist($purchaseReturnItem);
            $em->flush();
           // $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($purchaseItem,'purchase-return');
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'purchase-return');
            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->updatePurchaseReturnTotalPrice($entity);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('medicine_purchase_return_edit',array('id'=>$entity->getId())));
        }
        return $this->render('Medicine/PurchaseReturn/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $form->createView(),
        ));
    }
    /**
     * Deletes a PurchaseReturn entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->find($id);
        $this->allPurchaseReturnItemDelete($entity);
	    $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('medicine_purchase_return'));
    }

	public function allPurchaseReturnItemDelete(MedicinePurchaseReturn $invoice){

		$em = $this->em;

		/* @var $particular MedicinePurchaseReturnItem */

		if(!empty($invoice->getMedicinePurchaseReturnItems())){

			foreach ($invoice->getMedicinePurchaseReturnItems() as $particular){

				//$item = $particular->getMedicinePurchaseItem();
				$stock = $particular->getMedicineStock();
				//$this->requestStack->getSession()->set('item', $item);
				$this->requestStack->getSession()->set('stock', $stock);
				$em->remove($particular);
				$em->flush();
				//$item = $this->requestStack->getSession()->get('item');
				$stock = $this->requestStack->getSession()->get('stock');
				//$this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($item,'purchase_return');
				$this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'purchase-return');

			}

		}


	}



	/**
     * Deletes a PurchaseReturn entity.
     *
     */
    public function deleteItemAction(MedicinePurchaseReturn $purchase,$id)
    {

        $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        if($config == $purchase->getMedicineConfig()->getId()){
		    $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturnItem::class)->find($id);
		    $stock = $entity->getMedicineStock();
		    $purchaseReturn = $entity->getMedicinePurchaseReturn()->getId();
		    $this->requestStack->getSession()->set('stock', $stock);
		    $this->requestStack->getSession()->set('purchaseReturn', $purchaseReturn);
		    // $purchaseItem = $entity->getMedicinePurchaseItem();
		    $em->remove($entity);
		    $em->flush();
		    $stock = $this->requestStack->getSession()->get('stock');
		    $purchaseReturn = $this->requestStack->getSession()->get('purchaseReturn');
		    // $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($purchaseItem,'purchase_return');
		    $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'purchase-return');
	        $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->updatePurchaseReturnTotalPrice($purchase);
	        $this->requestStack->getSession()->getFlashBag()->add(
			    'error',"Data has been deleted successfully"
		    );
        }

        return $this->redirect($this->generateUrl('medicine_purchase_return_edit',array('id' => $purchase->getId())));
    }

	public function invoiceDiscountUpdateAction(Request $request)
	{

		$em = $this->em;
		$discountType = $request->request->get('discountType');
		$discountCal = (float)$request->request->get('discount');
		$invoice = $request->request->get('purchase');
		$entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->find($invoice);
		$subTotal = $entity->getSubTotal();
		if($discountCal > 0){
			if($discountType == 'flat'){
				$total = ($subTotal  - $discountCal);
				$discount = $discountCal;
			}else{
				$discount = ($subTotal * $discountCal)/100;
				$total = ($subTotal  - $discount);
			}
			if($subTotal > $discount ){
				$entity->setDiscountType($discountType);
				$entity->setDiscountCalculation($discountCal);
				$entity->setDiscount(round($discount));
				$entity->setTotal(round($total));

			}
		}else{
			$entity->setDiscountType('percentage');
			$entity->setDiscountCalculation(0);
			$entity->setDiscount(0);
			$entity->setTotal($entity->getSubTotal());
		}
		$em->flush();
		$data = array(
			'subTotal' => $entity->getSubTotal(),
			'total' => $entity->getTotal(),
			'discount' => $entity->getDiscount(),

		);
		return new Response(json_encode($data));
		exit;
	}

    public function approveAction(MedicinePurchaseReturn $entity)
    {
        $em = $this->em;
        if (!empty($entity) and $entity->getProcess() != "approved") {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertMedicineAccountPurchaseReturn($entity);
            $em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseReturnGlobalTransaction($accountPurchase);
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;

    }

}

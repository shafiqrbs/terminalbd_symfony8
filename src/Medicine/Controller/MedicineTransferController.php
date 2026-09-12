<?php

namespace App\Medicine\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicineTransfer;
use App\Medicine\Entity\MedicinePurchaseItem;
use App\Medicine\Entity\MedicineTransferItem;
use App\Medicine\Form\MedicineTransferItemType;
use App\Medicine\Form\MedicineTransferType;
use App\Medicine\Form\TransferItemType;
use App\Medicine\Form\TransferType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * MedicineTransfer controller.
 *
 */
class MedicineTransferController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all MedicineTransfer entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $entity = new MedicineTransfer();
        $form = $this->createCreateForm($entity);
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineTransfer::class)->findBy(array('medicineConfig' => $config),array('created'=>'ASC'));
        return $this->render('Medicine/MedicineTransfer/index.html.twig', array(
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function newAction(){

        $entity = new MedicineTransfer();
        $form = $this->createCreateForm($entity);
        return $this->render('Medicine/MedicineTransfer/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineTransfer::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Medicine/MedicineTransfer/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Creates a new MedicineTransfer entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MedicineTransfer();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $entity->setMedicineConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('medicine_transfer_edit', array('id' => $entity->getId())));
        }

        return $this->render('Medicine/MedicineTransfer/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a MedicineTransfer entity.
     *
     * @param MedicineTransfer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MedicineTransfer $entity)
    {
        $global = $this->getUser()->getGlobalOption();
        $form = $this->createForm(TransferType::class, $entity, array('action' => $this->generateUrl('medicine_transfer_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'option' => $global));
        return $form;
    }

    /**
     * Displays a form to edit an existing MedicineTransfer entity.
     *
     */
    public function editAction(MedicineTransfer $entity)
    {
        $em = $this->em;
        $purchaseItem = new MedicineTransferItem();
        $form = $this->createEditForm($entity,$purchaseItem);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineTransfer entity.');
        }
        return $this->render('Medicine/MedicineTransfer/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
    * Creates a form to edit a MedicineTransfer entity.
    *
    * @param MedicineTransfer $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MedicineTransfer $entity,MedicineTransferItem $purchaseItem)
    {
        $form = $this->createForm(TransferItemType::class, $purchaseItem, array(
            'action' => $this->generateUrl('medicine_transfer_update', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing MedicineTransfer entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineTransfer::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineTransfer entity.');
        }
        $data = $request->request->all();
        $medicineTransferItem = new MedicineTransferItem();
        $form = $this->createEditForm($entity,$medicineTransferItem);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $medicineTransferItem->setMedicineTransfer($entity);

            $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($data['medicineStock']);
            $medicineTransferItem->setMedicineStock($stock);
          //  $purchaseItem = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->find($data['medicinePurchaseItem']);
           // $medicineTransferItem->setMedicinePurchaseItem($purchaseItem);
            $medicineTransferItem->setPurchasePrice($stock->getPurchasePrice());
            $medicineTransferItem->setSubTotal($medicineTransferItem->getPurchasePrice() * $medicineTransferItem->getQuantity());
            $em->persist($medicineTransferItem);
            $em->flush();
           // $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($purchaseItem,'purchase-return');
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'purchase-return');
            $this->em->getRepository(\App\Medicine\Entity\MedicineTransfer::class)->updateMedicineTransferTotalPrice($entity);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('medicine_transfer_edit',array('id'=>$entity->getId())));
        }
        return $this->render('Medicine/MedicineTransfer/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $form->createView(),
        ));
    }
    /**
     * Deletes a MedicineTransfer entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineTransfer::class)->find($id);
        $this->allMedicineTransferItemDelete($entity);
	    $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('medicine_transfer'));
    }

	public function allMedicineTransferItemDelete(MedicineTransfer $invoice){

		$em = $this->em;

		/* @var $particular MedicineTransferItem */

		if($invoice->getMedicineTransferItems()){

			foreach ($invoice->getMedicineTransferItems() as $particular){

				$stock = $particular->getMedicineStock();
				$this->requestStack->getSession()->set('stock', $stock);
				$em->remove($particular);
				$em->flush();
				$stock = $this->requestStack->getSession()->get('stock');
				$this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'purchase-return');

			}

		}


	}



	/**
     * Deletes a MedicineTransfer entity.
     *
     */
    public function deleteItemAction(MedicineTransfer $purchase,$id)
    {

        $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        if($config == $purchase->getMedicineConfig()->getId()){
		    $entity = $em->getRepository(\App\Medicine\Entity\MedicineTransferItem::class)->find($id);
		    $stock = $entity->getMedicineStock();
		    $medicineTransfer = $entity->getMedicineTransfer()->getId();
		    $this->requestStack->getSession()->set('stock', $stock);
		    $this->requestStack->getSession()->set('MedicineTransfer', $medicineTransfer);
		    // $purchaseItem = $entity->getMedicinePurchaseItem();
		    $em->remove($entity);
		    $em->flush();
		    $stock = $this->requestStack->getSession()->get('stock');
		    $medicineTransfer = $this->requestStack->getSession()->get('MedicineTransfer');
		    // $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($purchaseItem,'purchase_return');
		    $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'purchase-return');
	        $this->em->getRepository(\App\Medicine\Entity\MedicineTransfer::class)->updateMedicineTransferTotalPrice($purchase);
	        $this->requestStack->getSession()->getFlashBag()->add(
			    'error',"Data has been deleted successfully"
		    );
        }

        return $this->redirect($this->generateUrl('medicine_transfer_edit',array('id' => $purchase->getId())));
    }

	public function invoiceDiscountUpdateAction(Request $request)
	{

		$em = $this->em;
		$discountType = $request->request->get('discountType');
		$discountCal = (float)$request->request->get('discount');
		$invoice = $request->request->get('purchase');
		$entity = $em->getRepository(\App\Medicine\Entity\MedicineTransfer::class)->find($invoice);
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

    public function approveAction(MedicineTransfer $entity)
    {
        $em = $this->em;
        if (!empty($entity) and $entity->getProcess() != 'approved') {
            $payment = $_REQUEST['payment'];
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            $entity->setPayment($payment);
            $entity->setDue($entity->getTotal() - $entity->getPayment());
            $em->flush();
            $accountSales = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertMedicineAccountTransferInvoice($entity);
            $em->getRepository(\App\Accounting\Entity\Transaction::class)->salesGlobalTransaction($accountSales);
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;

    }

}

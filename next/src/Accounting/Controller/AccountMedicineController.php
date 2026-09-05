<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Form\AccountMedicinePurchaseType;
use App\Accounting\Form\AccountSalesInvoiceType;
use App\Accounting\Form\AccountSalesMedicineType;
use App\Medicine\Entity\MedicineSales;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\Accounting\Entity\AccountSales;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountSalesMedicine controller.
 *
 */
class AccountMedicineController extends AbstractController
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

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function salesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->findWithSearch($user,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent =array(20,29));
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        return $this->render('Accounting/AccountMedicine/sales.html.twig', array(
            'entities' => $pagination,
            'accountHead' => $accountHead,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_PURCHASE') or is_granted('ROLE_DOMAIN')"))]

	public function customerOutstandingAction()
	{
		$em = $this->em;
		$data =$_REQUEST;
		$globalOption = $this->getUser()->getGlobalOption();
		$entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($globalOption,$data);
		$pagination = $this->paginate($entities);
		return $this->render('Accounting/AccountMedicine/customerOutstanding.html.twig', array(
			'entities' => $pagination,
			'searchForm' => $data,
		));
	}



	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_PURCHASE') or is_granted('ROLE_DOMAIN')"))]

    public function purchaseAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($globalOption,$data);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent =array(5));
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        return $this->render('Accounting/AccountMedicine/purchase.html.twig', array(
            'entities' => $pagination,
            'accountHead' => $accountHead,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }


	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_PURCHASE') or is_granted('ROLE_DOMAIN')"))]

    public function vendorOutstandingAction()
    {
        $em = $this->em;
        $data =$_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorMedicineOutstanding($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/AccountMedicine/purchaseOutstanding.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_PURCHASE') or is_granted('ROLE_DOMAIN')"))]


	public function purchaseNewAction()
    {
        $em = $this->em;
        $entity = new AccountPurchase();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/AccountMedicine/purchaseNew.html.twig', array(
            'entity'    => $entity,
            'banks'     => $banks,
            'form'      => $form->createView(),
        ));
    }

    public function purchaseCreateAction(Request $request)
    {
        $entity = new AccountPurchase();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity->setProcessHead('medicine');
            $entity->setProcessType('Payment');
            if($entity->getPayment() < 0){
                $entity->setPurchaseAmount(abs($entity->getPayment()));
                $entity->setPayment(0);
                $entity->setProcessType('Opening');
                $entity->setTransactionMethod(null);
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_purchase_medicine'));
        }

        return $this->render('Accounting/AccountMedicine/purchaseNew.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a form to create a AccountPurchase entity.
     *
     * @param AccountPurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountPurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountMedicinePurchaseType::class, $entity, array('action' => $this->generateUrl('account_purchase_medicine_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES') or is_granted('ROLE_DOMAIN')"))]

	public function salesNewAction()
    {
        $em = $this->em;
        $entity = new AccountSales();
        $form   = $this->salesCreateForm($entity);
        return $this->render('Accounting/AccountMedicine/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function salesCreateAction(Request $request)
    {
        $entity = new AccountSales();
        $form = $this->salesCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setGlobalOption( $this->getUser()->getGlobalOption());
            if(!empty($this->getUser()->getProfile()->getBranches())){
                $entity->setBranches($this->getUser()->getProfile()->getBranches());
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_sales_medicine'));
        }

        return $this->render('Accounting/AccountMedicine/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a form to create a AccountSales entity.
     *
     * @param AccountSales $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */

    private function salesCreateForm(AccountSales $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountSalesMedicineType::class, $entity, array('action' => $this->generateUrl('account_sales_medicine_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

    /**
     * Displays a form to edit an existing AccountSales entity.
     *
     */
    public function duePaymentAction(MedicineSales $sales)
    {
        $em = $this->em;
        $global=$this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->findOneBy(array('globalOption'=>$global,'medicineSales'=>$sales->getId()));
        if (!$entity) {
            return $this->redirect($this->generateUrl('account_sales_medicine_new'));
        }
        $em = $this->em;
        $entity = new AccountSales();
        $entity->setGlobalOption( $this->getUser()->getGlobalOption());
        $entity->setMedicineSales($sales);
        $entity->setCustomer($sales->getCustomer());
        $entity->setTransactionMethod($this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1));
        $entity->setProcessHead('Due');
        if(!empty($this->getUser()->getProfile()->getBranches())){
            $entity->setBranches($this->getUser()->getProfile()->getBranches());
        }
        $em->persist($entity);
        $em->flush();
        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/AccountMedicine/invoice.html.twig', array(
            'sales'      => $sales,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Displays a form to create a new AccountSales entity.
     *
     */
    public function duePaymentUpdateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountSales entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('account_sales_medicine'));
        }
        return $this->render('Accounting/AccountMedicine/invoice.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a AccountSales entity.
     *
     * @param AccountSales $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(AccountSales $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountSalesInvoiceType::class, $entity, array('action' => $this->generateUrl('account_sales_medicine_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

    public function salesApproveAction(AccountSales $entity)
    {
        if (!empty($entity) and $entity->getProcess() != 'approved' ) {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $em->getRepository(\App\Accounting\Entity\AccountSales::class)->updateCustomerBalance($entity);
            if($entity->getMedicineSales()){
                $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->updateSalesPaymentReceive($entity);
            }
            // $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertAccountSalesTransaction($entity);
            return new Response('success');
        } else {
            return new Response('failed');
        }


    }

    public function salesMedicineReverseAction(AccountSales $entity){

	    $em = $this->em;
    	$this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->accountReverse($entity);
	    $entity->setProcess(null);
	    $entity->setApprovedBy(null);
	    $entity->setBalance(0);
	    $em->flush();
	    return $this->redirect($this->generateUrl('account_sales_medicine'));
    }

	public function purchaseMedicineReverseAction(AccountPurchase $entity){

		$em = $this->em;
		$this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountReverse($entity);
		$entity->setProcess(null);
		$entity->setApprovedBy(null);
		$entity->setBalance(0);
		$em->flush();
		return $this->redirect($this->generateUrl('account_purchase_medicine'));

	}


}

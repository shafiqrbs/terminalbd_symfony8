<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Form\AccountPurchaseType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Tool\Entity\GlobalOption;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountPurchase controller.
 *
 */
class AccountPurchaseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly ToolManager $toolManager,
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


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_PURCHASE') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($this->getUser(),$data);
        $groups = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->getProcessModes($globalOption->getId());
        $users = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->getCreatedUsers($globalOption->getId());
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        return $this->render('Accounting/AccountPurchase/index.html.twig', array(
            'option' => $globalOption,
            'entities' => $pagination,
            'overview' => $overview,
            'groups' => $groups,
            'users' => $users,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_PURCHASE') or is_granted('ROLE_DOMAIN')"))]

    public function vendorOutstandingAction()
    {

        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorInventoryOutstanding($globalOption,'inventory',$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/AccountPurchase/purchaseOutstanding.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    public function purchaseReturnAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchaseReturn::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountPurchaseReturn::class)->accountPurchaseOverview($globalOption,$data);
        return $this->render('Accounting/AccountPurchase/purchaseReturn.html.twig', array(
            'entities' => $pagination,
            'overview' => $overview,
            'searchForm' => $data,
        ));
    }

    /**
     * Creates a new AccountPurchase entity.
     */

    public function createAction(Request $request)
    {
        $entity = new AccountPurchase();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        /* @var $global GlobalOption */
	    $global = $this->getUser()->getGlobalOption();
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if($form->isSubmitted() && $form->isValid() && empty($method)){
            $em = $this->em;
            $entity->setGlobalOption($global);
            if($global->getMainApp()->getSlug() == 'miss'){
	            $entity->setProcessHead('medicine');
	            $entity->setCompanyName($entity->getMedicineVendor()->getCompanyName());
	            $entity->setMedicineVendor($entity->getMedicineVendor());
            }elseif($global->getMainApp()->getSlug() == 'inventory'){
	            $entity->setProcessHead('inventory');
	            $entity->setCompanyName($entity->getVendor()->getCompanyName());
	            $entity->setVendor($entity->getVendor());
            }elseif($global->getMainApp()->getSlug() == 'hms'){
	            $entity->setProcessHead('hospital');
	            $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
	            $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'restaurant'){
	            $entity->setProcessHead('restaurant');
	            $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
	            $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'hotel'){
	            $entity->setProcessHead('hotel');
	            $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
	            $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'institute'){
	            $entity->setProcessHead('institute');
	            $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
	            $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'business'){
                if (empty($entity->getAccountVendor()) and !empty($data['companyName']) and !empty($data['customerMobile'])){
                    $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                    $customer = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->newExistingCustomerForSales($global, $mobile, $data);
                    $entity->setAccountVendor($customer);
                }
	            $entity->setProcessHead('business');
	            $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
	            $entity->setAccountVendor($entity->getAccountVendor());
            }else{
                if (empty($entity->getAccountVendor()) and !empty($data['companyName']) and !empty($data['customerMobile'])){
                    $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                    $customer = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->newExistingCustomerForSales($global, $mobile, $data);
                    $entity->setAccountVendor($customer);
                }
                $entity->setAccountVendor($entity->getAccountVendor());
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setProcessHead('accounting');
            }
            if( in_array($entity->getProcessType(),array("Outstanding","Opening","Adjustment","Credit"))  and $entity->getPayment() > 0 ){
                $entity->setPurchaseAmount(abs($entity->getPayment()));
                $entity->setPayment(0);
                $entity->setTransactionMethod(null);
            }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }else{
                $datetime = new \DateTime("now");
                $entity->setUpdated($datetime);
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            if($this->getUser()->getCheckExistRole('ROLE_DOMAIN_ACCOUNTING_OPERATOR')){
                return $this->redirect($this->generateUrl('account_purchase_new'));
            }else{
                return $this->redirect($this->generateUrl('account_purchase'));
            }
        }elseif(($form->isSubmitted() && $form->isValid() && $method == 'cash') ||
            ($form->isSubmitted() && $form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isSubmitted() && $form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $em = $this->em;
            $entity->setGlobalOption($global);
            if($global->getMainApp()->getSlug() == 'miss'){
                $entity->setProcessHead('medicine');
                $entity->setCompanyName($entity->getMedicineVendor()->getCompanyName());
                $entity->setMedicineVendor($entity->getMedicineVendor());
            }elseif($global->getMainApp()->getSlug() == 'inventory'){
                $entity->setProcessHead('inventory');
                $entity->setCompanyName($entity->getVendor()->getCompanyName());
                $entity->setVendor($entity->getVendor());
            }elseif($global->getMainApp()->getSlug() == 'hms'){
                $entity->setProcessHead('hospital');
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'restaurant'){
                $entity->setProcessHead('restaurant');
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'hotel'){
                $entity->setProcessHead('hotel');
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'institute'){
                $entity->setProcessHead('institute');
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setAccountVendor($entity->getAccountVendor());
            }elseif($global->getMainApp()->getSlug() == 'business'){
                if (empty($entity->getAccountVendor()) and !empty($data['companyName']) and !empty($data['customerMobile'])){
                    $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                    $customer = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->newExistingCustomerForSales($global, $mobile, $data);
                    $entity->setAccountVendor($customer);
                }
                $entity->setAccountVendor($entity->getAccountVendor());
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setProcessHead('business');
            }else{
                if (empty($entity->getAccountVendor()) and !empty($data['companyName']) and !empty($data['customerMobile'])){
                    $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                    $customer = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->newExistingCustomerForSales($global, $mobile, $data);
                    $entity->setAccountVendor($customer);
                }
                $entity->setAccountVendor($entity->getAccountVendor());
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setProcessHead('accounting');
            }
            if( in_array($entity->getProcessType(),array("Outstanding","Opening","Adjustment","Credit"))  and $entity->getPayment() > 0 ){
                $entity->setPurchaseAmount(abs($entity->getPayment()));
                $entity->setPayment(0);
                $entity->setTransactionMethod(null);
            }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }else{
                $datetime = new \DateTime("now");
                $entity->setUpdated($datetime);
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            if($this->getUser()->getCheckExistRole('ROLE_DOMAIN_ACCOUNTING_OPERATOR')){
                return $this->redirect($this->generateUrl('account_purchase_new'));
            }else{
                return $this->redirect($this->generateUrl('account_purchase'));
            }
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/AccountPurchase/new.html.twig', array(
            'global' => $global,
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
        $global = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountPurchaseType::class, $entity, array('action' => $this->generateUrl('account_purchase_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal purchase',
                'novalidate' => 'novalidate',
            ), 'global' => $global));
        return $form;
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_PURCHASE') or is_granted('ROLE_DOMAIN_ACCOUNTING_OPERATOR') or is_granted('ROLE_DOMAIN')"))]
    public function newAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = new AccountPurchase();
        $form   = $this->createCreateForm($entity);
        return $this->render('Accounting/AccountPurchase/new.html.twig', array(
            'option'    => $globalOption,
            'entity'    => $entity,
            'form'      => $form->createView(),
        ));
    }

    /**
     * Finds and displays a AccountPurchase entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        return $this->render('Accounting/AccountPurchase/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing AccountPurchase entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Accounting/AccountPurchase/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a AccountPurchase entity.
    *
    * @param AccountPurchase $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(AccountPurchase $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(AccountPurchaseType::class, $entity, array('action' => $this->generateUrl('account_purchase_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'global' => $inventory));
        return $form;
    }
    /**
     * Edits an existing AccountPurchase entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('account_purchase_edit', array('id' => $id)));
        }

        return $this->render('Accounting/AccountPurchase/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }

    /**
     * Displays a form to edit an existing Expenditure entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Account Purchase entity.');
        }
        if($data['name'] == 'PurchaseAmount'){
            $entity->setPurchaseAmount(abs($data['value']));
            $entity->setPayment(0);
        }else{
            $entity->setPayment($data['value']);
        }
        $em->flush();
        exit;

    }

    public function approveAction(AccountPurchase $entity)
    {
        if (!empty($entity) and $entity->getProcess() != 'approved') {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
	        if(empty($entity->getTransactionMethod()) and in_array($entity->getProcessType(),array( 'Due','Advance','Expense'))){
		        $method = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
		        $entity->setTransactionMethod($method);
	        }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }
            $em->flush();
            $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->updateVendorBalance($entity);
	        if($entity->getPayment() > 0 and in_array($entity->getProcessType(),array( 'Due','Advance','Expense'))){
		        $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertPurchaseCash($accountPurchase);
	        }
            return new Response('success');
        }else{
            return new Response('failed');
        }
    }

    /**
     * Deletes a Expenditure entity.
     *
     */
    public function deleteAction(AccountPurchase $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
        exit;
    }

	public function autoSearchAction(Request $request)
	{
		$item = $_REQUEST['q'];
		if ($item) {
			$global = $this->getUser()->getGlobalOption();
			$item = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->searchAutoComplete($item,$global);
		}
		return new JsonResponse($item);
	}

	public function searchVendorNameAction($vendor)
	{
		return new JsonResponse(array(
			'id' => $vendor,
			'text' => $vendor
		));
	}

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

    public function purchaseReverseAction(AccountPurchase $entity){

        $em = $this->em;
        $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->accountCashReverse($entity,'Purchase');
        $entity->setProcess(null);
        $entity->setApprovedBy(null);
        $entity->setPayment(0);
        $entity->setPurchaseAmount(0);
        $entity->setTotalAmount(0);
        $entity->setBalance(0);
        $em->flush();
        return $this->redirect($this->generateUrl('account_purchase'));

    }

}

<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountSales;
use App\Accounting\Form\AccountSalesInvoiceType;
use App\Accounting\Form\AccountSalesType;
use App\Inventory\Entity\Sales;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountSales controller.
 *
 */
class AccountSalesController extends AbstractController
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

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->findWithSearch($user,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->receiveModeOverview($user,$data);
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        $groups = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getProcessModes($user->getGlobalOption()->getId());
        $users = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getCreatedUsers($user->getGlobalOption()->getId());
        return $this->render('Accounting/AccountSales/index.html.twig', array(
            'entities' => $pagination,
            'transactionMethods' => $transactionMethods,
            'users' => $users,
            'groups' => $groups,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function customerOutstandingAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/AccountSales/customerOutstanding.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function customerSummaryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSummary($globalOption,$data);
        return $this->render('AccountingBundle:AccountSales/Report:customerReport.html.twig', array(
            'entities' => $entities,
            'option' => $globalOption,
            'searchForm' => $data,
        ));

    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function userSummaryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->userSummary($globalOption,$data);
        if(isset($data['submit']) and $data['submit'] == 'print' and isset($data['employee']) and $data['employee']){
            return $this->render('AccountingBundle:AccountSales/Report:userSummary.html.twig', array(
                'entities' => $entities,
                'option' => $globalOption,
                'searchForm' => $data,
            ));
        }else{
            return $this->render('AccountingBundle:AccountSales/Report:userSummary.html.twig', array(
                'entities' => $entities,
                'option' => $globalOption,
                'searchForm' => $data,
            ));
        }
    }


    /**
     * Lists all AccountSalesReturn entities.
     *
     */
    public function salesReturnAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $entities = $em->getRepository(\App\Accounting\Entity\AccountSalesReturn::class)->findWithSearch($this->getUser(),$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSalesReturn::class)->salesReturnOverview($this->getUser(),$data);
        return $this->render('Accounting/AccountSales/salesReturn.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }

    /**
     * Creates a new AccountSales entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountSales();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $data = $request->request->all();
        if($form->isSubmitted() && $form->isValid() && empty($method)){
            $entity->setGlobalOption($option);
            $customer = $form["customer"]->getData();
            if (empty($customer) and !empty($data['customerMobile'])){
                $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->newExistingCustomerForSales($option, $mobile, $data);
                $entity->setCustomer($customer);

            }elseif (!empty($customer)){
                $mobile = $this->toolManager->specialExpClean($customer);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$option, 'mobile' => $mobile));
                $entity->setCustomer($customer);
            }

            if( in_array($entity->getProcessHead(),array("Debit"))){
                $entity->setTotalAmount(abs($entity->getAmount()));
                $entity->setAmount(0);
                $entity->setTransactionMethod(null);
            }else{
                $entity->setAmount(abs($entity->getAmount()));
            }
            if( in_array($entity->getProcessHead(),array('Credit','Discount'))){
                $entity->setTransactionMethod(null);
            }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if(isset($data['created']) and !empty($data['created'])){
                $created = $data['created'];
                $date = new \DateTime($created);
                $entity->setCreated($date);
                $entity->setUpdated($date);
            }elseif($accountConfig == 1){
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
                return $this->redirect($this->generateUrl('account_sales_new'));
            }else{
                return $this->redirect($this->generateUrl('account_sales'));
            }
        }elseif(($form->isSubmitted() && $form->isValid() && $method == 'cash') ||
            ($form->isSubmitted() && $form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isSubmitted() && $form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $entity->setGlobalOption($option);
            $customer = $form["customer"]->getData();
            if (empty($customer) and !empty($data['customerMobile'])){
                $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->newExistingCustomerForSales($option, $mobile, $data);
                $entity->setCustomer($customer);

            }elseif (!empty($customer)){
                $mobile = $this->toolManager->specialExpClean($customer);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$option, 'mobile' => $mobile));
                $entity->setCustomer($customer);
            }
            if( in_array($entity->getProcessHead(),array("Debit"))){
                $entity->setTotalAmount(abs($entity->getAmount()));
                $entity->setAmount(0);
                $entity->setTransactionMethod(null);
            }else{
                $entity->setAmount(abs($entity->getAmount()));
            }
            if( in_array($entity->getProcessHead(),array('Credit','Discount'))){
                $entity->setTransactionMethod(null);
            }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if(isset($data['created']) and !empty($data['created'])){
                $created = $data['created'];
                $date = new \DateTime($created);
                $entity->setCreated($date);
                $entity->setUpdated($date);
            }elseif($accountConfig == 1){
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
                return $this->redirect($this->generateUrl('account_sales_new'));
            }else{
                return $this->redirect($this->generateUrl('account_sales'));
            }
        }

        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/AccountSales/new.html.twig', array(
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
    private function createCreateForm(AccountSales $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountSalesType::class, $entity, array('action' => $this->generateUrl('account_sales_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
      return $form;
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES') or is_granted('ROLE_DOMAIN_ACCOUNTING_OPERATOR') or is_granted('ROLE_DOMAIN')"))]
    public function newAction()
    {
        $em = $this->em;
        $entity = new AccountSales();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/AccountSales/new.html.twig', array(
            'entity' => $entity,
            'banks' => $banks,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new AccountSales entity.
     *
     */
    public function duePaymentAction(Sales $sales)
    {
        $em = $this->em;
        $entity = new AccountSales();
        $entity->setGlobalOption( $this->getUser()->getGlobalOption());
        $entity->setSales($sales);
        $entity->setCustomer($sales->getCustomer());
        $entity->setTransactionMethod($this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1));
        $entity->setProcessHead('Due');
        $em->persist($entity);
        $em->flush();
        $form   = $this->createEditForm($entity);
        return $this->render('Accounting/AccountSales/invoice.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a AccountSales entity.
     *
     */
    public function printAction(AccountSales $entity)
    {
        $em = $this->em;

        if (!$entity and $entity->getGlobalOption()->getId() != $this->getUser()->getGlobalOption()->getId() ) {
            throw $this->createNotFoundException('Unable to find AccountSales entity.');
        }
        $template = ($entity->getGlobalOption()->getAccountingConfig()->isCustomPrint() == 1) ? $entity->getGlobalOption()->getSubDomain():'print';
        $amountInWord = $this->toolManager->intToWords($entity->getAmount());
        $outstanding = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($entity->getGlobalOption(),$entity->getCustomer());
        return $this->render("Accounting/AccountSales/{$template}.html.twig", array(
            'entity'           => $entity,
            'config'           => $entity->getGlobalOption()->getAccountingConfig(),
            'outstanding'     => $outstanding,
            'amountInWord'     => $amountInWord,
        ));
    }

    /**
     * Displays a form to edit an existing AccountSales entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->find($id);

        if (!$entity and $entity->getGlobalOption()->getId() != $this->getUser()->getGlobalOption()->getId() ) {
            throw $this->createNotFoundException('Unable to find AccountSales entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/AccountSales/invoice.html.twig', array(
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
        $form = $this->createForm(AccountSalesInvoiceType::class, $entity, array('action' => $this->generateUrl('account_sales_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }
    /**
     * Edits an existing AccountSales entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountSales entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {

            if ($entity->getSales()->getDue() < $entity->getAmount() ){
                $this->requestStack->getSession()->getFlashBag()->add(
                    'notice',"Payment amount receive must be same or less as due amount"
                );
                return $this->redirect($this->generateUrl('account_sales_edit',array('id' => $entity->getId())));
            }
            $em->flush();
            return $this->redirect($this->generateUrl('account_sales'));
        }

        return $this->render('Accounting/AccountSales/invoice.html.twig', array(
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
        $entity = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        if($data['name'] == 'TotalAmount'){
            $entity->setTotalAmount(abs($data['value']));
            $entity->setAmount(0);
        }else{
            $entity->setAmount($data['value']);
        }
        $em->flush();
        exit;
    }

    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_SALES')]

    public function approveAction(AccountSales $entity)
    {
	    if (!empty($entity) and $entity->getProcess() != 'approved') {
		    $em = $this->em;
		    $entity->setProcess('approved');
		    $entity->setApprovedBy($this->getUser());
		    if(empty($entity->getTransactionMethod()) and in_array($entity->getProcessHead(),array( 'Due','Advance'))){
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
		    if(!empty($entity->getSales()) and $entity->getProcessHead() == 'Due'){
		    	$this->em->getRepository(\App\Inventory\Entity\Sales::class)->updateSalesPaymentReceive($entity);
		    }
		    $em->getRepository(\App\Accounting\Entity\AccountSales::class)->updateCustomerBalance($entity);
		    if($entity->getAmount() > 0 and in_array($entity->getProcessHead(),array( 'Due','Advance')) ){
                $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertSalesCash($entity);
		    }

            $customer = $entity->getCustomer();
            $name = $customer->getName();
            $orgName = $entity->getGlobalOption()->getName();
            $hotline = $entity->getGlobalOption()->getHotline();
            $mobile = "+88".$customer->getMobile();
           // $mobile = "+8801828148148";

            $outstanding = number_format($entity->getAmount(),2);

            $msg = "Sir, Your payment received BDT {$outstanding} TK, Thanks for being with us.";

            $msg = nl2br("=={$orgName}==  Dear {$msg}");
            if($entity->isSmsAlert() == 1 and $entity->getGlobalOption()->getAccountingConfig()->isSalesReceiveSms() == 1 and $entity->getGlobalOption()->getSmsSenderTotal() and $entity->getGlobalOption()->getSmsSenderTotal()->getRemaining() > 0 and $entity->getGlobalOption()->getNotificationConfig()->getSmsActive() == 1) {
                $this->send($msg,$mobile);
                $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertCustomerOutstandingSms($customer,$msg,'success');
                $this->requestStack->getSession()->getFlashBag()->add(
                    'success'," $name SMS has benn sent successfully"
                );
            }
		    return new Response('success');

        } else {

            return new Response('failed');
        }

    }

    /**
     * Deletes a Expenditure entity.
     *
     */
    public function deleteAction(AccountSales $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

    public function salesReverseAction(AccountSales $entity){

        $em = $this->em;
        $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->accountReverse($entity);
        $entity->setProcess(null);
        $entity->setApprovedBy(null);
        $entity->setTotalAmount(0);
        $entity->setAmount(0);
        $entity->setBalance(0);
        $em->flush();
        return $this->redirect($this->generateUrl('account_sales'));

    }


    public function salesPrint(AccountSales $sales)
    {
    	$em = $this->em;
	    $template = $sales->getGlobalOption()->getSlug();
	    $result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($sales->getGlobalOption(), $data = array('mobile'=> $sales->getCustomer()->getMobile()));
	    $balance = empty($result) ? 0 :$result[0]['customerBalance'];
	    $amountInWords = $this->toolManager->intToWords($sales->getAmount());
	    return  $this->render("Business/Print/{$template}.html.twig",
		    array(
			    'amountInWords' => $amountInWords,
			    'entity' => $sales,
			    'balance' => $balance,
			    'print' => 'print',
		    )
	    );
    }

    public function getCustomerLedgerAction()
    {

	    $customerId = $_REQUEST['customer'];
	    $globalOption = $this->getUser()->getGlobalOption();
	    $balance = 0;
	    $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $globalOption,'id'=> $customerId));
	    if(!empty($customer)){
		    $result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($globalOption,$customer);
		    $balance = empty($result) ? 0 : $result;
	    }
        $taka = number_format($balance).' Taka';
	    return new Response($taka);

    }

    public function getCustomerSalesLedgerAction()
    {

        $customerId = $_REQUEST['customer'];
        $globalOption = $this->getUser()->getGlobalOption();
        $balance = 0;
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $globalOption,'mobile'=> $customerId));
        if(!empty($customer)){
            $result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($globalOption,$customer);
            $balance = empty($result) ? 0 : $result;
        }
        $taka = number_format($balance).' Taka';
        return new Response($taka);

    }

    function send($msg, $phone, $sender = ""){

        $curl = curl_init();
        $data =array(
            'apikey' => '198a7497a859e5fe',
            'secretkey' => 'cb6adaba',
            'callerID' => '8809612770474',
            'toUser' => $phone,
            'messageContent' => $msg,
        );
        echo $phone;
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://smpp.ajuratech.com:7790/sendtext",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));
        $response = curl_exec($curl);
        var_dump($response);
        print_r(curl_error($curl));
        curl_close($curl);
    }

}

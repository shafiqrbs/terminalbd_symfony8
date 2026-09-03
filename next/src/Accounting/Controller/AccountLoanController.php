<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Accounting\Form\AccountLoanInvoiceType;
use App\Inventory\Entity\Sales;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use App\Accounting\Entity\AccountLoan;
use App\Accounting\Form\AccountLoanType;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountLoan controller.
 *
 */
class AccountLoanController extends Controller
{

    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all AccountLoan entities.
     *
     */
    public function indexAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $option = $this->getUser()->getGlobalOption();
        $transactionMethods = $this->getDoctrine()->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($option);
        if(isset($data['submit']) and $data['submit'] == 'print' and isset($data['employee']) and $data['employee']){
            $data[] = array('process'=>'Approved');
            $entities = $em->getRepository(\App\Accounting\Entity\AccountLoan::class)->findWithSearch($option,$data);
            $pagination = $entities->getQuery()->getResult();
            $employee = $data['employee'];
            $user = $this->getDoctrine()->getRepository(\App\User\Entity\User::class)->find($employee);
            return $this->render('Accounting/AccountLoan/print.html.twig', array(
                'entities' => $pagination,
                'user' => $user,
                'data' => $data,
            ));
        }else{
            $entities = $em->getRepository(\App\Accounting\Entity\AccountLoan::class)->findWithSearch($option,$data);
            $pagination = $this->paginate($entities);
            return $this->render('Accounting/AccountLoan/index.html.twig', array(
                'entities' => $pagination,
                'transactionMethods' => $transactionMethods,
                'employees' => $employees,
                'searchForm' => $data,
                'overview' => '',
            ));
        }

    }

     /**
     * Lists all AccountLoan entities.
     *
     */
    public function loanOutstandingAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountLoan::class)->outstandingLoan($option->getId());
        return $this->render('Accounting/AccountLoan/loanOutstanding.html.twig', array(
            'entities' => $entities,
            'option' => $option
        ));

    }

    /**
     * Creates a new AccountLoan entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountLoan();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption();
        $data = $request->request->all();
        $balance =  $em->getRepository(\App\Accounting\Entity\AccountLoan::class)->getLastBalance($option);
        $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
        if($entity->getTransactionType() == "Credit" and $entity->getAmount() > $balance ){
            $this->get('session')->getFlashBag()->add(
                'error',"This {$entity->getAmount()} amount must be went to equal or less then credit {$balance} amount"
            );
            return $this->render('Accounting/AccountLoan/new.html.twig', array(
                'entity' => $entity,
                'form'   => $form->createView(),
            ));
        }elseif($form->isValid() && empty($method)){
            $entity->setGlobalOption($option);
            if($entity->getTransactionType() == 'Credit') {
                $entity->setAmount("-{$entity->getAmount()}");
                $entity->setCredit(abs($entity->getAmount()));
            }else{
                $entity->setDebit($entity->getAmount());
            }
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
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_loan'));
        }elseif(($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $entity->setGlobalOption($option);
	        if($entity->getTransactionType() == 'Credit') {
                $entity->setAmount("-{$entity->getAmount()}");
                $entity->setCredit(abs($entity->getAmount()));
            }else{
                $entity->setDebit($entity->getAmount());
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
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_loan'));
        }

        $this->get('session')->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/AccountLoan/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a AccountLoan entity.
     *
     * @param AccountLoan $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountLoan $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into AccountLoanType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new AccountLoanType($globalOption), $entity, array(
            'action' => $this->generateUrl('account_loan_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal purchase',
                'novalidate' => 'novalidate',
            )
        ));
      return $form;
    }

    /**
     * Displays a form to create a new AccountLoan entity.
     *
     */
    public function newAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new AccountLoan();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/AccountLoan/new.html.twig', array(
            'entity' => $entity,
            'banks' => $banks,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Displays a form to edit an existing Expenditure entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountLoan::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        if($data['value'] > 0 ){
	        $entity->setAmount($data['value']);
        }
        $em->flush();
        exit;
    }

    public function approveAction(AccountLoan $entity)
    {
	    if (!empty($entity) and $entity->getProcess() != 'approved') {
		    $em = $this->getDoctrine()->getManager();
		    $entity->setProcess('approved');
		    $entity->setApprovedBy($this->getUser());
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }
		    $em->flush();
		    $em->getRepository(\App\Accounting\Entity\AccountLoan::class)->updateCustomerBalance($entity);
            $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->insertLoanCash($entity);
		    return new Response('success');

        } else {
            return new Response('failed');
        }
    }

    /**
     * Deletes a Expenditure entity.
     *
     */
    public function deleteAction(AccountLoan $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

    public function salesReverseAction(AccountLoan $entity){

        $em = $this->getDoctrine()->getManager();
        $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountLoan::class)->accountReverse($entity);
        $entity->setProcess(null);
        $entity->setApprovedBy(null);
        $entity->setAmount(0);
        $entity->setBalance(0);
        $em->flush();
        return $this->redirect($this->generateUrl('account_loan'));

    }


    public function salesPrint(AccountLoan $sales)
    {
    	$em = $this->getDoctrine()->getManager();
	    $template = $sales->getGlobalOption()->getSlug();
	    $result = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountLoan::class)->customerOutstanding($sales->getGlobalOption(), $data = array('mobile'=> $sales->getCustomer()->getMobile()));
	    $balance = empty($result) ? 0 :$result[0]['customerBalance'];
	    $amountInWords = $this->get('settong.toolManageRepo')->intToWords($sales->getAmount());
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
	    $customer = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $globalOption,'id'=> $customerId));
	    if(!empty($customer)){
		    $result = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountLoan::class)->customerSingleOutstanding($globalOption,$customer);
		    $balance = empty($result) ? 0 : $result;
	    }
        $taka = number_format($balance).' Taka';
	    return new Response($taka);
	    exit;

    }

    public function getCustomerSalesLedgerAction()
    {

        $customerId = $_REQUEST['customer'];
        $globalOption = $this->getUser()->getGlobalOption();
        $balance = 0;
        $customer = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $globalOption,'mobile'=> $customerId));
        if(!empty($customer)){
            $result = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountLoan::class)->customerSingleOutstanding($globalOption,$customer);
            $balance = empty($result) ? 0 : $result;
        }
        $taka = number_format($balance).' Taka';
        return new Response($taka);
        exit;

    }

}

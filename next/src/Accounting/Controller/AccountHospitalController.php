<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Form\AccountHmsPurchaseType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use App\Accounting\Entity\AccountSales;
use App\Accounting\Form\AccountSalesType;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountSalesHospital controller.
 *
 */
class AccountHospitalController extends Controller
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
     * Lists all AccountSales entities.
     *
     */
    public function salesAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->findWithSearch($user,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data);
        $accountHead = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent =array(20,29));
        $transactionMethods = $this->getDoctrine()->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        return $this->render('Accounting/AccountHospital/sales.html.twig', array(
            'entities' => $pagination,
            'accountHead' => $accountHead,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_SALES') or is_granted('ROLE_DOMAIN')"))]

	public function salesNewAction()
	{
		$em = $this->getDoctrine()->getManager();
		$entity = new AccountSales();
		$form   = $this->salesCreateForm($entity);
		return $this->render('Accounting/AccountHospital/new.html.twig', array(
			'entity' => $entity,
			'form'   => $form->createView(),
		));
	}



	/**
     * Lists all AccountSales entities.
     *
     */
    public function purchaseAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($globalOption,$data);
        $accountHead = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent =array(5));
        $transactionMethods = $this->getDoctrine()->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        return $this->render('Accounting/AccountHospital/purchase.html.twig', array(
            'entities' => $pagination,
            'accountHead' => $accountHead,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }

    public function purchaseNewAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new AccountPurchase();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/AccountHospital/purchaseNew.html.twig', array(
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
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity->setProcessHead('Account Purchase');
            if($entity->getPayment() < 0){
                $entity->setPurchaseAmount(abs($entity->getPayment()));
                $entity->setPayment(0);
                $entity->setTransactionMethod(null);
            }
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_purchase_hospital'));
        }

        return $this->render('Accounting/AccountHospital/purchaseNew.html.twig', array(
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
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into AccountHmsPurchaseType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new AccountHmsPurchaseType($globalOption), $entity, array(
            'action' => $this->generateUrl('account_purchase_hospital_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }


}

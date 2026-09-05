<?php

namespace App\Accounting\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Accounting\Entity\PettyCash;
use App\Accounting\Form\PettyCashType;
use Symfony\Component\HttpFoundation\Response;

/**
 * PettyCash controller.
 *
 */
class PettyCashController extends AbstractController
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
     * Lists all PettyCash entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\PettyCash::class)->findBy(array('globalOption' => $globalOption,'parent'=>NULL));
        $pagination = $this->paginate($entities);
        //$overview = $this->em->getRepository(\App\Accounting\Entity\PettyCash::class)->salesOverview($inventory,$data);
        $accountHead = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(array('status'=>1),array('name'=>'asc'));
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1));
        $accountBanks = $em->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(array('globalOption'=>$globalOption,'status'=>1));
        $accountMobileBank = $em->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(array('globalOption'=>$globalOption,'status'=>1));
        return $this->render('Accounting/PettyCash/index.html.twig', array(
            'entities' => $pagination,
            'transactionMethods' => $transactionMethods,
            'accountBanks' => $accountBanks,
            'accountMobileBank' => $accountMobileBank,
            'overview' => '',
        ));
    }
    /**
     * Creates a new PettyCash entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $entity = new PettyCash();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $lastBalance = $em->getRepository(\App\Accounting\Entity\PettyCash::class)->lastInsertCash($entity);
            $em = $this->em;
            $entity->setBalance($lastBalance + $entity->getAmount());
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('account_pettycash'));
        }
        return $this->render('Accounting/PettyCash/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a PettyCash entity.
     *
     * @param PettyCash $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(PettyCash $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PettyCashType::class, $entity, array('action' => $this->generateUrl('account_pettycash_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
      return $form;
    }

    /**
     * Displays a form to create a new PettyCash entity.
     *
     */
    public function newAction()
    {
        $em = $this->em;
        $entity = new PettyCash();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/PettyCash/new.html.twig', array(
            'entity' => $entity,
            'banks' => $banks,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a PettyCash entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\PettyCash::class)->find($id);
         if (!$entity) {
            throw $this->createNotFoundException('Unable to find PettyCash entity.');
        }

        return $this->render('Accounting/PettyCash/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing PettyCash entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $pettyCash = $em->getRepository(\App\Accounting\Entity\PettyCash::class)->find($data['pk']);
        if (!$pettyCash) {
            throw $this->createNotFoundException('Unable to find PettyCash entity.');
        }
        $pettyCash->setAmount($data['value']);
        $em->flush();
        exit;
    }

    /**
     * Deletes a PettyCash entity.
     *
     */
    public function deleteAction(PettyCash $pettyCash)
    {
        $em = $this->em;
        if (!$pettyCash) {
            throw $this->createNotFoundException('Unable to find PettyCash entity.');
        }
        $em->remove($pettyCash);
        $em->flush();
        return new Response('success');
        exit;
    }

    public function paymentAction(Request $request)
    {
        $data = $request->request->all();
        $entity = new PettyCash();
        $em = $this->em;
        $entity->setGlobalOption($this->getUser()->getGlobalOption());
        $parent = $em->getRepository(\App\Accounting\Entity\PettyCash::class)->find($data['parent']);
        $returnAmount = ($parent->getReturnAmount() + $data['amount']);
        $parent->setReturnAmount($returnAmount);
        $entity->setParent($parent);
        $entity->setToUser($parent->getCreatedBy());
        $entity->setAmount($data['amount']);
        $entity->setRemark($data['remark']);
        if($data['transactionMethod'] == 2){
            $entity->setAccountBank($em->getRepository(\App\Accounting\Entity\AccountBank::class)->find($data['accountBank']));
            $entity->setTransactionMethod($em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find($data['transactionMethod']));
        }elseif($data['transactionMethod'] == 3 ){
            $entity->setAccountBkash($em->getRepository(\App\Accounting\Entity\AccountBkash::class)->find($data['accountBkash']));
            $entity->setTransactionMethod($em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find($data['transactionMethod']));
        }else{
            $entity->setTransactionMethod($em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1));
        }
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->returnPettyCashTransaction($entity);
        return new Response('success');
        exit;

    }

    public function approveAction(PettyCash $pettyCash)
    {
        if (!empty($pettyCash)) {
            $em = $this->em;
            $pettyCash->setProcess('approved');
            $pettyCash->setApprovedBy($this->getUser());
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertPettyCash($pettyCash);
            $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertPettyCashTransaction($pettyCash);

            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }

}

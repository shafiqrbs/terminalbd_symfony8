<?php

namespace App\Accounting\Controller;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountCondition;
use App\Accounting\Entity\AccountConditionLedger;
use App\Accounting\Form\AccountConditionLedgerType;
use App\Accounting\Form\AccountConditionType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\DomainUser\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;


/**
 * Customer controller.
 *
 */
class AccountConditionController extends AbstractController
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

    #[IsGranted(new Expression("is_granted('ROLE_ACCOUNTING') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_ACCOUNTING_CONDITION')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountCondition::class)->findBy(array('globalOption'=>$globalOption),array('name'=>'ASC'));
        return $this->render('Accounting/AccountCondition/index.html.twig', array(
            'entities' => $entities,
            'searchForm' => $data,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_ACCOUNTING') or is_granted('ROLE_DOMAIN')"))]

    public function ledgerAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $pagination = $em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->getLedgerLists($globalOption->getId(),$data);
        $entities = $this->paginate($pagination);
        return $this->render('Accounting/AccountCondition/ledger.html.twig', array(
            'entities' => $entities,
            'searchForm' => $data,
        ));
    }

    /**
     * Creates a new Customer entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountCondition();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $option = $this->getUser()->getGlobalOption();
        if ($form->isValid()) {
            $em = $this->em;
            $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setGlobalOption($option);
	        $entity->setMobile($mobile);
	        $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->insertConditionAccount($entity);
            return $this->redirect($this->generateUrl('account_condition'));
        }
        return $this->render('Accounting/AccountCondition/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Customer entity.
     *
     * @param Customer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountCondition $entity)
    {
        $form = $this->createForm(new AccountConditionType(), $entity, array(
            'action' => $this->generateUrl('account_condition_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function newAction()
    {
        $entity = new AccountCondition();
        $form   = $this->createCreateForm($entity);
        return $this->render('Accounting/AccountCondition/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $entity = $this->em->getRepository(\App\Accounting\Entity\AccountCondition::class)->findOneBy(array('globalOption'=>$option,'id'=>$id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/AccountCondition/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Customer entity.
    *
    * @param AccountCondition $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(AccountCondition $entity)
    {
        $form = $this->createForm(new AccountConditionType(), $entity, array(
            'action' => $this->generateUrl('account_condition_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing Customer entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $entity = $this->em->getRepository(\App\Accounting\Entity\AccountCondition::class)->findOneBy(array('globalOption'=>$option,'id'=>$id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
	        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->insertConditionAccount($entity);
            return $this->redirect($this->generateUrl('account_condition'));
        }
        return $this->render('Accounting/AccountCondition/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction($id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $entity = $this->em->getRepository(\App\Accounting\Entity\AccountCondition::class)->findOneBy(array('globalOption'=>$option,'id'=>$id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }
        return $this->redirect($this->generateUrl('account_condition'));
    }


    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function ledgerNewAction()
    {
        $entity = new AccountConditionLedger();
        $form   = $this->createLedgerForm($entity);
        return $this->render('Accounting/AccountCondition/new-ledger.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Customer entity.
     *
     * @param Customer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createLedgerForm(AccountConditionLedger $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into AccountConditionLedgerType::configureOptions() and pass them as form options: $option), $entity, array(
        $form = $this->createForm(new AccountConditionLedgerType($option), $entity, array(
            'action' => $this->generateUrl('account_condition_ledger_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Creates a new AccountConditionLedger entity.
     *
     */
    public function createLedgerAction(Request $request)
    {
        $entity = new AccountConditionLedger();
        $form = $this->createLedgerForm($entity);
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        if($form->isValid() && empty($method)){
            $entity->setGlobalOption($option);
            if( in_array($entity->getMode(),array("Debit"))){
                $entity->setDebit(abs($entity->getAmount()));
                $entity->setAmount(0);
                $entity->setTransactionMethod(null);
            }else{
                $entity->setCredit(abs($entity->getAmount()));
                $entity->setAmount(abs($entity->getAmount()));
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
            return $this->redirect($this->generateUrl('account_condition_ledger'));
        }elseif(($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $entity->setGlobalOption($option);
            if( in_array($entity->getMode(),array("Debit"))){
                $entity->setDebit(abs($entity->getAmount()));
                $entity->setAmount(0);
                $entity->setTransactionMethod(null);
            }else{
                $entity->setCredit(abs($entity->getAmount()));
                $entity->setAmount('-'.$entity->getAmount());
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
            return $this->redirect($this->generateUrl('account_condition_ledger'));
        }

        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/AccountCondition/new-ledger.html.twig', array(
            'entity' => $entity,
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
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountConditionLedger entity.');
        }
        $amount = abs(floatval($data['value']));
        if($data['name'] == 'Debit'){
            $entity->setDebit(abs($amount));
            $entity->setAmount(abs($amount));
        }else{
            $entity->setCredit(abs($amount));
            $entity->setAmount('-'.$amount);
        }
        $em->flush();
        exit;
    }

    /**
     * Displays a form to edit an existing Expenditure entity.
     *
     */
    public function deleteLedgerAction($id)
    {

        $option = $this->getUser()->getGlobalOption();
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->findOneBy(array('globalOption'=>$option,'id'=>$id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountConditionLedger entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }

    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_SALES')]

    public function approveAction(AccountConditionLedger $entity)
    {
        if (!empty($entity) and $entity->getProcess() != 'approved') {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            if(empty($entity->getTransactionMethod()) and in_array($entity->getMode(),array( 'Due','Advance'))){
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
            $em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->updateConditionBalance($entity);
            if($entity->getAmount() > 0 and in_array($entity->getMode(),array( 'Due','Advance')) ){
                $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountConditionCash($entity);
            }
            return new Response('success');

        } else {

            return new Response('failed');
        }

    }




}

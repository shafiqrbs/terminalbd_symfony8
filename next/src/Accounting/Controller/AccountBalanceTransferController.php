<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\Accounting\Entity\AccountBalanceTransfer;
use App\Accounting\Form\AccountBalanceTransferType;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountBalanceTransfer controller.
 *
 */
class AccountBalanceTransferController extends AbstractController
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

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

	public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $entities = $em->getRepository(\App\Accounting\Entity\AccountBalanceTransfer::class)->findWithSearch( $this->getUser(),$data);
        $pagination = $this->paginate($entities);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(array('isParent'=>1),array('name'=>'ASC'));
        $debit = $this->em->getRepository(\App\Accounting\Entity\AccountBalanceTransfer::class)->accountCashOverview($this->getUser(),'Debit',$data);
        $credit = $this->em->getRepository(\App\Accounting\Entity\AccountBalanceTransfer::class)->accountCashOverview($this->getUser(),'Credit',$data);
        $overview = array('debit' => $debit,'credit' => $credit);
        return $this->render('Accounting/AccountBalanceTransfer/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
            'overview' => $overview,
            'accountHead' => $accountHead,
        ));
    }
    /**
     * Creates a new AccountBalanceTransfer entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountBalanceTransfer();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $method = empty($entity->getFromTransactionMethod()) ? '' : $entity->getFromTransactionMethod()->getSlug();
        if (($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getFromAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getFromAccountMobileBank())
        ) {
            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            if(!empty($this->getUser()->getProfile()->getBranches())){
                $entity->setBranches($this->getUser()->getProfile()->getBranches());
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_balancetransfer'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/AccountBalanceTransfer/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a AccountBalanceTransfer entity.
     *
     * @param AccountBalanceTransfer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountBalanceTransfer $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountBalanceTransferType::class, $entity, array('action' => $this->generateUrl('account_balancetransfer_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
      return $form;
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $em = $this->em;
        $entity = new AccountBalanceTransfer();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/AccountBalanceTransfer/new.html.twig', array(
            'entity' => $entity,
            'banks' => $banks,
            'form'   => $form->createView(),
        ));
    }



    /**
     * Displays a form to edit an existing AccountBalanceTransfer entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountBalanceTransfer::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountBalanceTransfer entity.');
        }
        $entity->setAmount($data['value']);
        $em->flush();
        exit;
    }

    public function approveAction(AccountBalanceTransfer $entity)
    {
        if (!empty($entity) and $entity->getProcess() != 'approved') {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->balanceTransferAccountCash($entity,'BalanceTransfer');
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(AccountBalanceTransfer $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountBalanceTransfer entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
        exit;
    }


    /**
     * Deletes a AccountBalanceTransfer entity.
     *
     */
    public function approveDeleteAction(AccountBalanceTransfer $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountBalanceTransfer entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }


	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

	public function journalReverseAction(AccountBalanceTransfer $entity){

		$em = $this->em;
		$this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->accountCashReverse($entity,'BalanceTransfer');
		$entity->setProcess(null);
		$entity->setApprovedBy(null);
		$em->flush();
		return $this->redirect($this->generateUrl('account_balancetransfer'));

	}

}

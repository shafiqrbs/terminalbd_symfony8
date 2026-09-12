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
use App\Accounting\Entity\AccountPurchaseCommission;
use App\Accounting\Form\AccountPurchaseCommissionType;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountPurchaseCommission controller.
 *
 */
class AccountPurchaseCommissionController extends AbstractController
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
        $entities = $em->getRepository(\App\Accounting\Entity\AccountPurchaseCommission::class)->findWithSearch( $this->getUser()->getGlobalOption(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/AccountPurchaseCommission/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }
    /**
     * Creates a new AccountPurchaseCommission entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountPurchaseCommission();
        $form = $this->createCreateForm($entity);
        $global = $this->getUser()->getGlobalOption();
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isSubmitted() && $form->isValid() && $method == 'cash') ||
            ($form->isSubmitted() && $form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isSubmitted() && $form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $em = $this->em;
            $global = $this->getUser()->getGlobalOption();
            $entity->setGlobalOption($global);
            if(!empty($this->getUser()->getProfile()->getBranches())){
                $entity->setBranches($this->getUser()->getProfile()->getBranches());
            }
            if($global->getMainApp()->getSlug() == 'miss'){
                $entity->setCompanyName($entity->getMedicineVendor()->getCompanyName());
                $entity->setMedicineVendor($entity->getMedicineVendor());
            }elseif($global->getMainApp()->getSlug() == 'inventory'){
                $entity->setCompanyName($entity->getVendor()->getCompanyName());
                $entity->setVendor($entity->getVendor());
            }else{
                $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
                $entity->setAccountVendor($entity->getAccountVendor());
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_purchasecommission'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/AccountPurchaseCommission/new.html.twig', array(
            'entity' => $entity,
            'option' => $global,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a AccountPurchaseCommission entity.
     *
     * @param AccountPurchaseCommission $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountPurchaseCommission $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountPurchaseCommissionType::class, $entity, array('action' => $this->generateUrl('account_purchasecommission_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'global' => $globalOption));
      return $form;
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $em = $this->em;
        $entity = new AccountPurchaseCommission();
        $form   = $this->createCreateForm($entity);
        $global = $this->getUser()->getGlobalOption();
        return $this->render('Accounting/AccountPurchaseCommission/new.html.twig', array(
            'entity' => $entity,
            'option' => $global,
            'form'   => $form->createView(),
        ));
    }



    /**
     * Displays a form to edit an existing AccountPurchaseCommission entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountPurchaseCommission::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchaseCommission entity.');
        }
        $entity->setSales($data['value']);
        $profit = (($entity->getSales() * $entity->getProfitPercentage())/100);
        $entity->setProfit($profit);
        $purchase = ($entity->getSales() - $profit);
        $entity->setPurchase($purchase);
        $em->flush();
        exit;
    }

    public function approveAction(AccountPurchaseCommission $entity)
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
            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertPurchaseCommission($entity);
           // $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseCommissionTransaction($this->getUser()->getGlobalOption(),$entity);
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(AccountPurchaseCommission $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchaseCommission entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
        exit;
    }


    /**
     * Deletes a AccountPurchaseCommission entity.
     *
     */
    public function approveDeleteAction(AccountPurchaseCommission $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchaseCommission entity.');
        }
        $em->remove($entity);
        $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->approvedDeleteRecord($entity,'BalanceTransfer');
        $em->flush();
        return new Response('success');
        exit;
    }


	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

	public function journalReverseAction(AccountPurchaseCommission $entity){

		$em = $this->em;
		$this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountReverse($entity);
		$entity->setProcess(null);
		$entity->setApprovedBy(null);
		$entity->setAmount(0);
		$em->flush();
		return $this->redirect($this->generateUrl('account_purchasecommission'));

	}

}

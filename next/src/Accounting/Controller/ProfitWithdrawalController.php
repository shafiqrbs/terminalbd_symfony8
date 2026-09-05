<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountHead;
use App\Accounting\Form\ProfitWithdrawalType;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\Accounting\Entity\AccountJournal;
use App\Accounting\Form\AccountJournalType;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountJournal controller.
 *
 */
class ProfitWithdrawalController extends AbstractController
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
        $data['mode'] = "withdrawal";
        $entities = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->findWithSearch( $this->getUser(),$data);
        $pagination = $this->paginate($entities);
       $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($this->getUser()->getGlobalOption());
        return $this->render('Accounting/ProfitWithdrawal/index.html.twig', array(
            'entities' => $pagination,
            'overview' => '',
            'searchForm' => $data,
            'employees' => $employees,
        ));
    }

    /**
     * Creates a new AccountJournal entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountJournal();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ){
            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            if($entity->getTransactionMethod()->getSlug() == "cash"){
                $cash = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findOneBy(array("slug" => "cash-in-hand"));
                $entity->setAccountHeadCredit($cash);
            }elseif($entity->getTransactionMethod()->getSlug() == "bank"){
                $bank = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findOneBy(array("slug" => "bank-account"));
                $entity->setAccountHeadCredit($bank);
            }elseif($entity->getTransactionMethod()->getSlug() == "mobile"){
                $mobile = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findOneBy(array("slug" => "mobile-account"));
                $entity->setAccountHeadCredit($mobile);
            }
            $profit = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findOneBy(array("slug" => "profit-loss"));
            $entity->setAccountHeadDebit($profit);
            $entity->setMode("withdrawal");
            $entity->setTransactionType("Credit");
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_profit_withdrawal'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/ProfitWithdrawal/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a AccountJournal entity.
     *
     * @param AccountJournal $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountJournal $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(ProfitWithdrawalType::class, $entity, array('action' => $this->generateUrl('account_profit_withdrawal_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
      return $form;
    }


	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $em = $this->em;
        $entity = new AccountJournal();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(array('isParent' => 1),array('name'=>'ASC'));
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());
        return $this->render('Accounting/ProfitWithdrawal/new.html.twig', array(
            'entity' => $entity,
            'accountHead' => $accountHead,
            'heads' => $heads,
            'banks' => $banks,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a AccountJournal entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->find($id);
         if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }

        return $this->render('Accounting/AccountJournal/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/AccountJournal/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a AccountJournal entity.
    *
    * @param AccountJournal $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(AccountJournal $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(ProfitWithdrawalType::class, $entity, array('action' => $this->generateUrl('account_journal_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }
    /**
     * Edits an existing AccountJournal entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('account_journal_edit', array('id' => $id)));
        }

        return $this->render('Accounting/AccountJournal/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }


    public function paymentAction(Request $request)
    {
        $data = $request->request->all();
        $entity = new AccountJournal();
        $em = $this->em;
        $entity->setGlobalOption($this->getUser()->getGlobalOption());
        $parent = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->find($data['parent']);
        $entity->setToUser($parent->getCreatedBy());
        $entity->setAmount($data['amount']);
        $entity->setRemark($data['remark']);
        $em->persist($entity);
        $em->flush();
        exit;
    }

    /**
     * Displays a form to edit an existing AccountJournal entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $entity->setAmount($data['value']);
        $em->flush();
        exit;
    }

    public function approveAction(AccountJournal $entity)
    {
        if (!empty($entity) and $entity->getProcess() != 'approved') {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            $accountClose = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountClose == 1){
                $datetime = new \DateTime("yesterday 23:59:59");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }
            $em->flush();
            if(!empty($entity->getTransactionMethod())){
	            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountCash($entity,'Journal');
            }
            $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertAccountJournalTransaction($entity);
            return new Response('success');
        } else {
            return new Response('failed');
        }

    }


	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(AccountJournal $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }


    /**
     * Deletes a AccountJournal entity.
     *
     */
    public function stakeholderProfitAction(User $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $amount = $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->getStakeHolderProfitAccount($entity);
        return new Response(abs($amount));
    }


    /**
     * Deletes a AccountJournal entity.
     *
     */
    public function approveDeleteAction(AccountJournal $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }


	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

	public function journalReverseAction(AccountJournal $entity){

		$em = $this->em;
		$this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->accountReverse($entity);
		$entity->setProcess(null);
		$entity->setApprovedBy(null);
		$entity->setAmount(0);
		$em->flush();
		return $this->redirect($this->generateUrl('account_profit_withdrawal'));

	}

	public function journalPdfAction()
	{

		set_time_limit(0);
		ignore_user_abort(true);
		$array = array();
		$em = $this->em;
		$data = $_REQUEST;
		$entities = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->findWithSearch( $this->getUser(),$data);
		$entities = $entities->getResult();
		$array[] = 'Created,User Name,Method,Ref No,Account Head Debit,Account Head Credit,Debit,Credit';

		/* @var $entity AccountJournal */

		foreach ($entities as $key => $entity){

			$method = !empty($entity['methodName']) ? $entity['methodName']:'';
			$creditHead = !empty($entity['accountHeadDebitName']) ? $entity['accountHeadDebitName']:'';
			$debitHead = !empty($entity['accountHeadCreditName']) ? $entity['accountHeadCreditName']:'';

			$debit = $entity['transactionType'] == 'Debit' ? $entity['amount']:'';
			$credit = $entity['transactionType'] == 'Credit' ? $entity['amount']:'';
			$startDate = isset($data['startDate'])  ? $data['startDate'] : '';
			$rows = array(
				$entity['updated']->format('d-m-Y'),
				$entity['userName'],
				$method,
				$entity['accountRefNo'],
				$debitHead,
				$creditHead,
				$debit,
				$credit
			);
			$array[] = implode(',', $rows);
		}
		$startDate = isset($data['startDate'])  ? $data['startDate'] : '';
		$compareStart = new \DateTime($startDate);
		$start =  $compareStart->format('d-m-Y');
		$fileName = $start.'-account-journal.csv';
		$content = implode("\n", $array);
		$response = new Response($content);
		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
		return $response;
	}

}

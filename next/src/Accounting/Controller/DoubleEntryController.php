<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Symfony\Component\Form\Form;
use App\Accounting\Entity\AccountJournalItem;
use App\Accounting\Form\DoubleEntryType;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Accounting\Entity\AccountJournal;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountJournal controller.
 *
 */
class DoubleEntryController extends AbstractController
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

        $data['mode'] = "double-entry";
        $entities = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->findDoubleEntrySearch( $this->getUser(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/DoubleEntry/index.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function journalDetailsAction()
    {
        $option = $this->getUser()->getGlobalOption();
        $em = $this->em;
        $data = $_REQUEST;

        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1],['name'=>'ASC']);
         $accountSubHeads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['globalOption' => $option],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());

        $entities = $em->getRepository(\App\Accounting\Entity\AccountJournalItem::class)->findDoubleEntrySearch( $this->getUser(),$data);
        if(empty($data['pdf'])){

            $pagination = $this->paginate($entities);
            return $this->render('Accounting/DoubleEntry/journalItem.html.twig', [
                'accountHead' => $accountHead,
                'accountSubHeads' => $accountSubHeads,
                'heads' => $heads,
                'entities' => $pagination,
                'searchForm' => $data,
            ]);

        }else{
            $html = $this->renderView(
                'Accounting/DoubleEntry/journalItemPdf.html.twig', [
                    'globalOption' => $option,
                    'accountHead' => $accountHead,
                    'accountSubHeads' => $accountSubHeads,
                    'entities' => $entities->getResult(),
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'journal-item.pdf');
        }


    }

    /**
     * Creates a new AccountJournal entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountJournal();
        $data = $request->request->all();
        $form   = $this->createCreateForm($entity);
        if($data['totalDebit'] == $data['totalDebit']){

            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity->setMode('double-entry');
            $entity->setAmount($data['totalDebit']);
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountJournalItem::class)->insertDoubleEntry($entity,$data);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_double_entry'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());

        return $this->render('Accounting/DoubleEntry/new.html.twig', [
            'entity' => $entity,
            'accountHead' => $accountHead,
            'heads' => $heads,
            'form' => $form->createView(),
        ]);

    }

    /**
     * Creates a form to edit a AccountJournal entity.
     *
     * @param AccountJournal $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(AccountJournal $entity)
    {
        $form = $this->createForm(DoubleEntryType::class, $entity, [
            'action' => $this->generateUrl('account_double_entry_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }


	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $entity = new AccountJournal();
        $em = $this->em;
        $form   = $this->createCreateForm($entity);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());
        return $this->render('Accounting/DoubleEntry/new.html.twig', [
            'entity' => $entity,
            'accountHead' => $accountHead,
            'heads' => $heads,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a AccountJournal entity.
     *
     */
    public function showAction($id)
    {
        exit;
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->find($id);
        $editForm = $this->createEditForm($entity);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());

        $subHeads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccount( $this->getUser()->getGlobalOption()->getId(),$entity);

        return $this->render('Accounting/DoubleEntry/new.html.twig', [
            'entity' => $entity,
            'accountHead' => $accountHead,
            'heads' => $heads,
            'subHeads' => $subHeads,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a AccountJournal entity.
     *
     * @param AccountJournal $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(AccountJournal $entity)
    {
            $form = $this->createForm(DoubleEntryType::class, $entity, [
            'action' => $this->generateUrl('account_double_entry_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
            ]
        ]);
        return $form;
    }


    /**
     * Edits an existing AccountJournal entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $data = $request->request->all();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $editForm = $this->createEditForm($entity);
        if($data['totalDebit'] == $data['totalDebit']){

            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity->setMode('double-entry');
            $entity->setAmount($data['totalDebit']);
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountJournalItem::class)->insertDoubleEntry($entity,$data);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_double_entry_edit',['id'=> $entity->getId()]));

        }
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());

        $subHeads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccount( $this->getUser()->getGlobalOption()->getId(),$entity);

        return $this->render('Accounting/DoubleEntry/new.html.twig', [
            'entity'      => $entity,
            'heads'      => $heads,
            'subHeads'      => $subHeads,
            'form'   => $editForm->createView(),
        ]);
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
                $datetime = new DateTime("yesterday 23:59:59");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertDoubleEntryTransaction($entity);
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


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function itemDeleteAction(AccountJournalItem $item)
    {
        $em = $this->em;
        if (!$item) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $em->remove($item);
        $em->flush();
        return new Response('success');
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
        $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->approvedDeleteRecord($entity,'Journal');
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
		return $this->redirect($this->generateUrl('account_double_entry'));

	}

	public function journalPdfAction()
	{

		set_time_limit(0);
		ignore_user_abort(true);
		$array = [];
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
			$startDate = $data['startDate'] ?? '';
			$rows = [
				$entity['updated']->format('d-m-Y'),
				$entity['userName'],
				$method,
				$entity['accountRefNo'],
				$debitHead,
				$creditHead,
				$debit,
				$credit
			];
			$array[] = implode(',', $rows);
		}
		$startDate = $data['startDate'] ?? '';
		$compareStart = new DateTime($startDate);
		$start =  $compareStart->format('d-m-Y');
		$fileName = $start.'-account-journal.csv';
		$content = implode("\n", $array);
		$response = new Response($content);
		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
		return $response;
	}


    public function approveJournalResetAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data['mode'] = "double-entry";
        $entities = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->findDoubleEntrySearch( $this->getUser(),$data);
        $pagination = $entities->getResult();

        /* @var $entity AccountJournal */

        foreach ( $pagination as $entity ){
            if (!empty($entity) and $entity->getProcess() == 'approved') {
                $em->createQuery("DELETE AccountingBundle:AccountCash e WHERE e.globalOption = {$entity->getGlobalOption()->getId()} AND e.accountJournal ={$entity->getId()}")->execute();
                $em->createQuery("DELETE AccountingBundle:Transaction e WHERE e.globalOption = {$entity->getGlobalOption()->getId()} AND e.accountJournal ={$entity->getId()}")->execute();
                $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->resetDoubleEntryTransaction($entity);
            } else {
                return new Response('failed');
            }
        }
        return $this->redirect($this->generateUrl('account_double_entry'));

    }

    public function downloadPdf($html,$fileName = '')
    {
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename={$fileName}");
        echo $pdf;
        return new Response('');
    }

}

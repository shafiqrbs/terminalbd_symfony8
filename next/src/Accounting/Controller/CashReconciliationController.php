<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\Accounting\Entity\CashReconciliationMeta;
use Knp\Snappy\Pdf;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Accounting\Entity\CashReconciliation;
use Symfony\Component\HttpFoundation\Response;

/**
 * CashReconciliationController controller.
 *
 */
class CashReconciliationController extends AbstractController
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

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

	public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $entities = $em->getRepository(\App\Accounting\Entity\CashReconciliation::class)->findWithSearch( $this->getUser()->getGlobalOption(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/CashReconciliation/index.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $em = $this->getDoctrine()->getManager();
        $global = $this->getUser()->getGlobalOption();
        $date = new DateTime('now');
        $entity = new CashReconciliation();
        $exist = $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->checkExist($global);
        if(!$exist){
            $entity->setGlobalOption($global);
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new DateTime("yesterday 23:30:30");
                $entity->setCreated($datetime);
                $date = $datetime->format("Y-m-d");
            }else{
                $datetime = new DateTime("now");
                $entity->setCreated($datetime);
                $date = $datetime->format("Y-m-d");
            }
            $em->persist($entity);
            $em->flush();
            $data = ['startDate' => $date,'endDate'=>$date];
            $bankCash = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
            $mobileCash = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
            $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->initialUpdate($this->getUser(),$entity);
            $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->notesReconciliationInsert($entity,$bankCash,$mobileCash);
            return $this->redirect($this->generateUrl('account_cashreconciliation_edit',['id' => $entity->getId()]));
        }else{
            $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->systemCashUpdate($this->getUser(),$exist);
            return $this->redirect($this->generateUrl('account_cashreconciliation_edit',['id' => $exist->getId()]));
        }
    }
    
    public function editAction($id){

        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Accounting\Entity\CashReconciliation::class)->findOneBy(['globalOption' => $option,'id' => $id]);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CashReconciliation entity.');
        }
        $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
        if($accountConfig == 1){
            $datetime = new DateTime("yesterday 23:30:30");
            $date = $datetime->format("Y-m-d");
        }else{
            $datetime = new DateTime("now");
            $date = $datetime->format("Y-m-d");
        }
        $data = ['startDate' => $date,'endDate'=>$date];
        $transactionCashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionWiseOverview( $this->getUser(),$data);

        $transactionBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
        $transactionMobileBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
        $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->systemCashUpdate($this->getUser(),$entity);
        return $this->render('Accounting/CashReconciliation/new.html.twig', [
            'entity' => $entity,
            'transactionCashOverviews'          => $transactionCashOverview,
            'transactionBankCashOverviews'          => $transactionBankCashOverviews,
            'transactionMobileBankCashOverviews'    => $transactionMobileBankCashOverviews,

        ]);
    }

    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Accounting\Entity\CashReconciliation::class)->findOneBy(['globalOption' => $option,'id' => $id]);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CashReconciliation entity.');
        }
        $transactionBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser());
        $transactionMobileBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser());
        return $this->render('Accounting/CashReconciliation/show.html.twig', [
            'entity' => $entity,
            'transactionBankCashOverviews'          => $transactionBankCashOverviews,
            'transactionMobileBankCashOverviews'    => $transactionMobileBankCashOverviews,

        ]);
    }

    /**
     * Displays a form to edit an existing CashReconciliation entity.
     *
     */
    public function metaUpdateAction(CashReconciliationMeta $entity)
    {
        $amount = $_REQUEST['amount'];
        $note = $_REQUEST['note'];
        $meta = $_REQUEST['metaKey'];
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CashReconciliation entity.');
        }
        $entity->setAmount($amount);
        $entity->setNoteNo($note);
        if(!empty($meta)){
            $entity->setMetaKey($meta);
        }
        $em->flush();
        $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->update($entity->getCashReconciliation(),$entity->getTransactionMethod());
        return new Response('success');
        exit;
    }

    public function addAction(CashReconciliation $reconciliation)
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $method = $data['method'];
        $particular = $data['particular'];
        $amount = $data['amount'];
        $entity = new CashReconciliationMeta();
        $entity->setCashReconciliation($reconciliation);
        $entity->setTransactionMethod($method);
        $entity->setMetaKey($particular);
        $entity->setAmount($amount);
        $entity->setIsCustom(1);
        $em->persist($entity);
        $em->flush();
        $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->update($reconciliation,$entity->getTransactionMethod());
        return new Response('success');
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(CashReconciliation $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CashReconciliation entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
        exit;
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function metaDeleteAction(CashReconciliationMeta $entity)
    {
        $em = $this->getDoctrine()->getManager();
        $recon = $entity->getCashReconciliation();
        $method = $entity->getTransactionMethod();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CashReconciliation entity.');
        }
        $em->remove($entity);
        $em->flush();
        $this->getDoctrine()->getRepository(\App\Accounting\Entity\CashReconciliation::class)->update($recon,$method);
        return new Response('success');
        exit;
    }

    /**
     * Deletes a CashReconciliation entity.
     *
     */
    public function approveDeleteAction(CashReconciliation $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CashReconciliation entity.');
        }
        $em->remove($entity);
        $this->getDoctrine()->getRepository(\App\Accounting\Entity\Transaction::class)->approvedDeleteRecord($entity,'BalanceTransfer');
        $em->flush();
        return new Response('success');
        exit;
    }

	#[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

	public function journalReverseAction(CashReconciliation $entity){

		$em = $this->getDoctrine()->getManager();
		$this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountReverse($entity);
		$entity->setProcess(null);
		$entity->setApprovedBy(null);
		$entity->setAmount(0);
		$em->flush();
		return $this->redirect($this->generateUrl('account_cashreconciliation'));

	}

    #[IsGranted('ROLE_DOMAIN')]

	public function reportDownloadAction( $id){


        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Accounting\Entity\CashReconciliation::class)->findOneBy(['globalOption' => $option,'id' => $id]);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CashReconciliation entity.');
        }
        $transactionBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser());
        $transactionMobileBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser());
        $html = $this->renderView('Accounting/CashReconciliation/pdf.html.twig', [
            'entity' => $entity,
            'transactionBankCashOverviews'          => $transactionBankCashOverviews,
            'transactionMobileBankCashOverviews'    => $transactionMobileBankCashOverviews,

        ]);
        $date = $entity->getCreated()->format('d-m-Y');
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=reconciliation-{$date}.pdf");
        echo $pdf;


    }

}

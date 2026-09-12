<?php

namespace App\Hospital\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Hospital\Entity\HmsInvoiceReturn;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceParticular;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * InvoiceReturn controller.
 *
 */
class InvoiceReturnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    /**
     * Lists all Vendor entities.
     *
     */

    public function paginate($entities)
    {

        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_HMS') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->invoiceLists( $user, $data);
        $pagination = $this->paginate($entities);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,[5]);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,[5,6]);
        return $this->render('Hospital/InvoiceReturn/index.html.twig', [
            'entities' => $pagination,
            'assignDoctors' => $assignDoctors,
            'referredDoctors' => $referredDoctors,
            'searchForm' => $data,
        ]);

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_ADMIN') or is_granted('ROLE_DOMAIN')"))]

    public function createAction($id)
    {
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(['hospitalConfig' => $hospital, 'id' => $id]);
        if(!empty($entity->getHmsInvoiceReturn())){
            return $this->redirect($this->generateUrl('hms_invoicereturn'));
        }
        return $this->render('Hospital/InvoiceReturn/confirm.html.twig', [
            'entity' => $entity,
        ]);

    }

    public function paymentAction(Invoice $invoice)
    {
        $em = $this->em;
        $request = $_REQUEST;
        $payment = $request['payment'];
        $remark = $request['remark'];
        if ( (!empty($invoice)  and empty($invoice->HmsInvoiceReturn) and !empty($payment)) and $invoice->getTotal() >= $payment ) {
            $em = $this->em;
            $entity = new HmsInvoiceReturn();
            $entity->setHospitalConfig($invoice->getHospitalConfig());
            $entity->setHmsInvoice($invoice);
            $entity->setRemark($remark);
            $entity->setAmount($payment);
            $entity->setProcess('In-progress');
            $em->persist($entity);
            $em->flush();
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }

    public function canceledAction(InvoiceParticular $particular)
    {
        $em = $this->em;
        $particular->setProcess('Canceled');
        $particular->setParticularDeliveredBy(null);
        $em->flush();
        return new Response('success');
    }

    public function approveAction(HmsInvoiceReturn $invoice )
    {
        $em = $this->em;
        if(!empty($invoice) and $invoice->getProcess() != 'Approved'){
            $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->hmsReturnInvoice($invoice);
            $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateProcess($invoice);
            $invoice->setProcess('Approved');
            $em->persist($invoice);
            $em->flush($invoice);
            return new Response('success');
        }else{
            return new Response('failed');
        }
        exit;


    }

    public function deleteAction(HmsInvoiceReturn $return )
    {
        $em = $this->em;
        if (!$return) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($return);
        $em->flush();
        return new Response('success');
        exit;
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InvoiceReturn entity.');
        }

        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('hms_InvoiceReturn'));
    }

}

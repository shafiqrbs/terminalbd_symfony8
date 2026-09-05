<?php

namespace App\Tool\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\InvoiceSmsEmail;
use App\Tool\Form\InvoiceSmsEmailType;
use Symfony\Component\HttpFoundation\Response;

/**
 * InvoiceSmsEmail controller.
 *
 */
class InvoiceSmsEmailController extends AbstractController
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
            20  /*limit per page*/
        );
        return $pagination;
    }


    #[IsGranted('ROLE_DOMAIN')]

    public function indexAction()
    {
        $em = $this->em;
        $option = $_REQUEST['option'] ?? '';
        if (empty($option) and $this->isGranted('ROLE_SUPER_ADMIN')) {
            $entities = $em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->findBy([], ['updated' => 'DESC']);
        }else{
            $option = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($option);
            $entities = $em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->findBy(['globalOption' => $option],['updated'=>'desc']);
        }
        $entities = $this->paginate($entities);
        return $this->render('Tool/InvoiceSmsEmail/index.html.twig', [
            'entities' => $entities,
            'globalOption' => $option,
        ]);
    }

    /**
     * Lists all InvoiceSmsEmail entities.
     *
     */
    public function domainAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->findBy(['globalOption'=>$globalOption],['updated'=>'desc']);
        $entities = $this->paginate($entities);
        return $this->render('Tool/InvoiceSmsEmail/index.html.twig', [
            'entities' => $entities,
        ]);
    }


     /**
     * Displays a form to create a new InvoiceSmsEmail entity.
     *
     */
    public function newAction(GlobalOption $option)
    {
        $entity = new InvoiceSmsEmail();
        $em = $this->em;
        $entity->setGlobalOption($option);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('invoicesmsemail_edit', ['invoice' => $entity->getInvoice()]));

    }

    /**
     * Finds and displays a InvoiceSmsEmail entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InvoiceSmsEmail entity.');
        }
        return $this->render('Tool/InvoiceSmsEmail/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing InvoiceSmsEmail entity.
     *
     */
    public function editAction(InvoiceSmsEmail $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InvoiceSmsEmail entity.');
        }

        $editForm = $this->createEditForm($entity);
        $sePricing = $this->em->getRepository(\App\Tool\Entity\SmsEmailPricing::class)->findBy(['status'=>1],['quantity'=>'asc']);

        $itemIds = [];
        foreach ($entity->getInvoiceSmsEmailItems() as $row ){
            $itemIds[] = $row->getSmsEmailPricing()->getId();;
        }

        return $this->render('Tool/InvoiceSmsEmail/new.html.twig', [
            'entity'      => $entity,
            'itemIds'      => $itemIds,
            'packagePricing'      => $sePricing,
            'form'   => $editForm->createView(),

        ]);
    }

    /**
     * Creates a form to edit a InvoiceSmsEmail entity.
     *
     * @param InvoiceSmsEmail $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(InvoiceSmsEmail $entity)
    {
        $form = $this->createForm(InvoiceSmsEmailType::class, $entity, [
            'action' => $this->generateUrl('invoicesmsemail_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing InvoiceSmsEmail entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InvoiceSmsEmail entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $data = $request->request->all();
            $intlMobile = $entity->getPaymentMobile();
            $mobile = $this->toolManager->specialExpClean($intlMobile);
            $entity->setPaymentMobile($mobile);
            if($entity->getTransactionMethod()->getId() > 0){
                $entity->setProcess('In-progress');
            }
            $em->flush();
            $this->em->getRepository(\App\Tool\Entity\InvoiceSmsEmailItem::class)->insertItem($entity,$data);
            $this->em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->updateInvoice($entity);

            return $this->redirect($this->generateUrl('invoicesmsemail_show', ['id' => $id]));
        }

        return $this->render('Tool/InvoiceSmsEmail/show.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a InvoiceSmsEmail entity.
     *
     */
    public function deleteAction(InvoiceSmsEmail $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InvoiceSmsEmail entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('invoicesmsemail'));
    }

    /**
     * Deletes a InvoiceSmsEmailItem entity.
     *
     */
    public function deleteItemAction($invoice ,$package)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\InvoiceSmsEmailItem::class)->findOneBy(['invoiceSmsEmail'=> $invoice,'smsEmailPricing' => $package]);
        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        $this->em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->updateInvoice($entity->getInvoiceSmsEmail());
        exit;


    }

    /**
     * Edits an existing InvoiceSmsEmail entity.
     *
     */
    public function paymentAction(Request $request,InvoiceSmsEmail $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InvoiceSmsEmail entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $intlMobile = $entity->getPaymentMobile();
            $mobile = $this->toolManager->specialExpClean($intlMobile);
            $entity->setPaymentMobile($mobile);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Data has been updated successfully"
            );
        }
        $this->em->getRepository(\App\Tool\Entity\InvoiceSmsEmail::class)->updateInvoice($entity);


        exit;

    }

    /**
     * Deletes a InvoiceSmsEmail entity.
     *
     */
    public function approveAction(InvoiceSmsEmail $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InvoiceSmsEmail entity.');
        }
        if($entity->getProcess() == 'Pending'){
            $entity->setProcess('In-progress');
        }elseif($entity->getProcess() == 'In-progress'){
            $entity->setProcess('Done');
            $entity->setReceivedBy($this->getUser());
        } if($entity->getProcess() == 'Pending') {
            $entity->setProcess('Done');
            $entity->setReceivedBy($this->getUser());
        }

        $em->getRepository(\App\Tool\Entity\SmsSenderTotal::class)->updateSmsBundle($entity);

        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been updated successfully"
        );
        return new Response('success');
    }



}

<?php

namespace App\Tool\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\SmsEmailPricing;
use App\Tool\Form\SmsEmailPricingType;

/**
 * SmsEmailPricing controller.
 *
 */
class SmsEmailPricingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all SmsEmailPricing entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\SmsEmailPricing::class)->findAll();

        return $this->render('Tool/SmsEmailPricing/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new SmsEmailPricing entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SmsEmailPricing();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setAmount($entity->getQuantity() * $entity->getPrice());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('smspricing'));
        }

        return $this->render('Tool/SmsEmailPricing/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a SmsEmailPricing entity.
     *
     * @param SmsEmailPricing $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(SmsEmailPricing $entity)
    {
        $form = $this->createForm(SmsEmailPricingType::class, $entity, [
            'action' => $this->generateUrl('smspricing_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new SmsEmailPricing entity.
     *
     */
    public function newAction()
    {
        $entity = new SmsEmailPricing();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/SmsEmailPricing/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a SmsEmailPricing entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SmsEmailPricing::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SmsEmailPricing entity.');
        }

        return $this->render('Tool/SmsEmailPricing/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing SmsEmailPricing entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SmsEmailPricing::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SmsEmailPricing entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Tool/SmsEmailPricing/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a SmsEmailPricing entity.
     *
     * @param SmsEmailPricing $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(SmsEmailPricing $entity)
    {
        $form = $this->createForm(SmsEmailPricingType::class, $entity, [
            'action' => $this->generateUrl('smspricing_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
       return $form;
    }
    /**
     * Edits an existing SmsEmailPricing entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SmsEmailPricing::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SmsEmailPricing entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $entity->setAmount($entity->getQuantity() * $entity->getPrice());
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('smspricing'));
        }
        return $this->render('Tool/SmsEmailPricing/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a SmsEmailPricing entity.
     *
     */
    public function deleteAction(SmsEmailPricing $entity)
    {

            $em = $this->em;
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SmsEmailPricing entity.');
            }

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );
            return $this->redirect($this->generateUrl('smspricing'));
    }



    /**
     * Status a Page entity.
     *
     */
    public function statusAction(SmsEmailPricing $entity)
    {

        $em = $this->em;
        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('smspricing'));
    }

}

<?php

namespace App\Tool\Controller;

use App\Tool\Service\ToolManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\PortalMobileBankAccount;
use App\Tool\Form\PortalMobileBankAccountType;

/**
 * PortalMobileBankAccount controller.
 *
 */
class PortalMobileBankAccountController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly ToolManager $toolManager,
    ) {
    }


    /**
     * Lists all PortalMobileBankAccount entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\PortalMobileBankAccount::class)->findAll();

        return $this->render('Tool/PortalMobileBankAccount/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new PortalMobileBankAccount entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new PortalMobileBankAccount();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $intlMobile = $entity->getMobile();
            $mobile = $this->toolManager->specialExpClean($intlMobile);
            $entity->setMobile($mobile);
            $name = $entity->getMobile().','.$entity->getServiceName().','.$entity->getAccountType();
            $entity->setName($name);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Status has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('mobilebankaccount'));
        }

        return $this->render('Tool/PortalMobileBankAccount/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a PortalMobileBankAccount entity.
     *
     * @param PortalMobileBankAccount $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(PortalMobileBankAccount $entity)
    {
        $form = $this->createForm(PortalMobileBankAccountType::class, $entity, [
            'action' => $this->generateUrl('mobilebankaccount_create'),
            'method' => 'POST',
        ]);
         return $form;
    }

    /**
     * Displays a form to create a new PortalMobileBankAccount entity.
     *
     */
    public function newAction()
    {
        $entity = new PortalMobileBankAccount();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/PortalMobileBankAccount/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a PortalMobileBankAccount entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\PortalMobileBankAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalMobileBankAccount entity.');
        }
       return $this->render('Tool/PortalMobileBankAccount/show.html.twig', [
            'entity'      => $entity,
       ]);
    }

    /**
     * Displays a form to edit an existing PortalMobileBankAccount entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\PortalMobileBankAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalMobileBankAccount entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Tool/PortalMobileBankAccount/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a PortalMobileBankAccount entity.
     *
     * @param PortalMobileBankAccount $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(PortalMobileBankAccount $entity)
    {
        $form = $this->createForm(PortalMobileBankAccountType::class, $entity, [
            'action' => $this->generateUrl('mobilebankaccount_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        return $form;
    }
    /**
     * Edits an existing PortalMobileBankAccount entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\PortalMobileBankAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalMobileBankAccount entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $intlMobile = $entity->getMobile();
            $mobile = $this->toolManager->specialExpClean($intlMobile);
            $entity->setMobile($mobile);
            $name = $entity->getMobile().','.$entity->getServiceName().','.$entity->getAccountType();
            $entity->setName($name);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('mobilebankaccount_edit', ['id' => $id]));
        }

        return $this->render('Tool/PortalMobileBankAccount/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a PortalMobileBankAccount entity.
     *
     */
    public function deleteAction(PortalMobileBankAccount $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalMobileBankAccount entity.');
        }

        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('mobilebankaccount'));
    }

    /**
     * Status a PortalMobileBankAccount entity.
     *
     */
    public function statusAction(PortalMobileBankAccount $entity)
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
        return $this->redirect($this->generateUrl('mobilebankaccount'));
    }



}

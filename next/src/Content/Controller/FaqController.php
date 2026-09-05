<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Content\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use App\Content\Form\FaqType;

/**
 * Faq controller.
 *
 */
class FaqController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Faq entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();

        $entities = $em->getRepository(\App\Content\Entity\Page::class)->findBy(['globalOption'=>$globalOption,'module'=>11],['created'=>'desc']);

        return $this->render('Content/Faq/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new Faq entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $user = $this->getUser();
            $entity->setUser($user);
            $entity ->setModule($this->em->getRepository(\App\Tool\Entity\Module::class)->findOneBy(['slug' => 'faq']));
            $entity->setGlobalOption($user->getGlobalOption());
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('faq'));
        }

        return $this->render('Content/Faq/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Faq entity.
     *
     * @param Page $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Page $entity)
    {
        $form = $this->createForm(FaqType::class, $entity, [
            'action' => $this->generateUrl('faq_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;

    }

    /**
     * Displays a form to create a new Page entity.
     *
     */
    public function newAction()
    {
        $entity = new Page();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/Faq/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Faq entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Faq entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/Faq/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Faq entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Faq entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/Faq/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Page entity.
     *
     * @param Page $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Page $entity)
    {
        $form = $this->createForm(FaqType::class, $entity, [
            'action' => $this->generateUrl('faq_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;
    }
    /**
     * Edits an existing Faq entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Faq entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('faq_edit', ['id' => $id]));
        }

        return $this->render('Content/Faq/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a Page entity.
     *
     */
    public function deleteAction(Page $entity)
    {
        $em = $this->em;
        if(!empty($entity->getFile())){
            $entity->removeUpload();
        }
        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been updated successfully");
        return $this->redirect($this->generateUrl('faq'));
    }

    /**
     * Creates a form to delete a Faq entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('faq_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }

    /**
     * Status a news entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        $em = $this->em;
        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('faq'));
    }
}

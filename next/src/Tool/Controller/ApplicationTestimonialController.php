<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\ApplicationTestimonial;
use App\Tool\Form\ApplicationTestimonialType;

/**
 * ApplicationTestimonial controller.
 *
 */
class ApplicationTestimonialController extends AbstractController
{

    /**
     * Lists all ApplicationTestimonial entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository(\App\Tool\Entity\ApplicationTestimonial::class)->findAll();

        return $this->render('Tool/ApplicationTestimonial/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ApplicationTestimonial entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ApplicationTestimonial();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('applicationtestimonial_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/ApplicationTestimonial/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ApplicationTestimonial entity.
     *
     * @param ApplicationTestimonial $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ApplicationTestimonial $entity)
    {
        $form = $this->createForm(new ApplicationTestimonialType(), $entity, [
            'action' => $this->generateUrl('applicationtestimonial_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ApplicationTestimonial entity.
     *
     */
    public function newAction()
    {
        $entity = new ApplicationTestimonial();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/ApplicationTestimonial/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ApplicationTestimonial entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\ApplicationTestimonial::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ApplicationTestimonial entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/ApplicationTestimonial/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing ApplicationTestimonial entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\ApplicationTestimonial::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ApplicationTestimonial entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/ApplicationTestimonial/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ApplicationTestimonial entity.
     *
     * @param ApplicationTestimonial $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ApplicationTestimonial $entity)
    {
        $form = $this->createForm(new ApplicationTestimonialType(), $entity, [
            'action' => $this->generateUrl('applicationtestimonial_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
       return $form;
    }
    /**
     * Edits an existing ApplicationTestimonial entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\ApplicationTestimonial::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ApplicationTestimonial entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $entity->upload();
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('applicationtestimonial_edit', ['id' => $id]));
        }

        return $this->render('Tool/ApplicationTestimonial/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a ApplicationTestimonial entity.
     *
     */
    public function deleteAction(ApplicationTestimonial $entity)
    {
            $em = $this->getDoctrine()->getManager();
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ApplicationTestimonial entity.');
            }
            $entity->removeUpload();
            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );
            return $this->redirect($this->generateUrl('theme'));
    }

    /**
     * Creates a form to delete a ApplicationTestimonial entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('applicationtestimonial_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        //$data = $request->request->all();


        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Tool\Entity\ApplicationTestimonial::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('theme'));
    }

}

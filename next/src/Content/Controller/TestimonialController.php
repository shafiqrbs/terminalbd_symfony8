<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Content\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Form\TestimonialType;

/**
 * Testimonial controller.
 *
 */
class TestimonialController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all Testimonial entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Content\Entity\Page::class)->findBy(['globalOption'=> $globalOption,'module'=> 3],['name' => 'asc']);
        return $this->render('Content/Testimonial/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new Page entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $entity->setUser($this->getUser());
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity ->setModule($this->em->getRepository(\App\Tool\Entity\Module::class)->find(3));
            $entity->upload();
            $em->flush();

            return $this->redirect($this->generateUrl('testimonial'));
        }

        return $this->render('Content/Testimonial/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Testimonial entity.
     *
     * @param Page $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Page $entity)
    {

        $form = $this->createForm(TestimonialType::class, $entity, [
            'action' => $this->generateUrl('testimonial_create'),
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

        return $this->render('Content/Testimonial/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Page entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Testimonial entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/Testimonial/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Page entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Testimonial entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/Testimonial/new.html.twig', [
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


        $form = $this->createForm(TestimonialType::class, $entity, [
            'action' => $this->generateUrl('testimonial_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }
    /**
     * Edits an existing Page entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Testimonial entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if( $entity->upload() and !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();

            return $this->redirect($this->generateUrl('testimonial_edit', ['id' => $id]));
        }

        return $this->render('Content/Testimonial/edit.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
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
        return $this->redirect($this->generateUrl('testimonial'));
    }

    /**
     * Creates a form to delete a Testimonial entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('testimonial_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}

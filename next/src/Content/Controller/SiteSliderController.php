<?php

namespace App\Content\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\SiteSlider;
use App\Content\Form\SiteSliderType;

/**
 * SiteSlider controller.
 *
 */
class SiteSliderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all SiteSlider entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Content\Entity\SiteSlider::class)->findAll();

        return $this->render('Content/SiteSlider/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new SiteSlider entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SiteSlider();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);

            // TODO(migration): AbstractController::get() was removed; inject this service.
            $uploadableManager = $this->get('stof_doctrine_extensions.uploadable.manager');

            // Here, "getMyFile" returns the "UploadedFile" instance that the form bound in your $myFile property
            $uploadableManager->markEntityToUpload($entity, $entity->getFile());

            $em->flush();

            return $this->redirect($this->generateUrl('siteslider'));
        }

        return $this->render('Content/SiteSlider/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a SiteSlider entity.
     *
     * @param SiteSlider $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(SiteSlider $entity)
    {

        $form = $this->createForm(new SiteSliderType(), $entity, [
            'action' => $this->generateUrl('siteslider_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;
    }

    /**
     * Displays a form to create a new SiteSlider entity.
     *
     */
    public function newAction()
    {
        $entity = new SiteSlider();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/SiteSlider/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a SiteSlider entity.
     * @param SiteSlider $slider
     * @return Response
     */
    public function showAction(SiteSlider $slider)
    {

        $deleteForm = $this->createDeleteForm($slider->getId());

        return $this->render('Content/SiteSlider/show.html.twig', [
            'entity'      => $slider,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing SiteSlider entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SiteSlider::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteSlider entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/SiteSlider/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a SiteSlider entity.
     *
     * @param SiteSlider $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(SiteSlider $entity)
    {

        $form = $this->createForm(new SiteSliderType(), $entity, [
            'action' => $this->generateUrl('siteslider_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }
    /**
     * Edits an existing SiteSlider entity.
     *
     */
    public function updateAction(Request $request, SiteSlider $entity )
    {
        $em = $this->em;

        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
	        $entity->upload();
            // TODO(migration): AbstractController::get() was removed; inject this service.
            //$uploadableManager = $this->get('stof_doctrine_extensions.uploadable.manager');
            // Here, "getMyFile" returns the "UploadedFile" instance that the form bound in your $myFile property
            //$uploadableManager->markEntityToUpload($entity, $entity->getFile());
            $em->flush();
            return $this->redirect($this->generateUrl('siteslider'));
        }

        return $this->render('Content/SiteSlider/edit.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a SiteSlider entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Content\Entity\SiteSlider::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SiteSlider entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('siteslider'));
    }

    /**
     * Creates a form to delete a SiteSlider entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('siteslider_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }
}

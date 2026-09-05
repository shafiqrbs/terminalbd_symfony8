<?php

namespace App\Content\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Content\Entity\Testimonial;
use App\Content\Form\SiteTestimonialType;
use Symfony\Component\HttpFoundation\Request;
use App\Content\Entity\SiteContent;


/**
 * SiteContent controller.
 *
 */
class SiteTestimonialController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all SiteContent entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $entities = $em->getRepository(\App\Content\Entity\Testimonial::class)->findAll();
        return $this->render('Content/SiteTestimonial/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new SiteContent entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Testimonial();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity ->upload();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('sitetestimonial'));
        }
        return $this->render('Content/SiteTestimonial/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a SiteContent entity.
     *
     * @param SiteContent $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Testimonial $entity)
    {
	    $form = $this->createForm(SiteTestimonialType::class, $entity, [
            'action' => $this->generateUrl('sitetestimonial_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
	    return $form;
    }

    /**
     * Displays a form to create a new SiteContent entity.
     *
     */
    public function newAction()
    {
        $entity = new Testimonial();
        $form   = $this->createCreateForm($entity);
        return $this->render('Content/SiteTestimonial/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a SiteContent entity.
     *
     */
    public function showAction($id)
    {

    }

    /**
     * Displays a form to edit an existing SiteContent entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Content\Entity\Testimonial::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteContent entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Content/SiteTestimonial/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a SiteContent entity.
     *
     * @param SiteContent $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Testimonial $entity)
    {
        $form = $this->createForm(SiteTestimonialType::class, $entity, [
            'action' => $this->generateUrl('sitetestimonial_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing SiteContent entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Testimonial::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteContent entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $em->flush();
            return $this->redirect($this->generateUrl('sitetestimonial'));
        }

        return $this->render('Content/SiteTestimonial/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a SiteContent entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
	    $em = $this->em;
    	$entity = $em->getRepository(\App\Content\Entity\Testimonial::class)->find($id);
	    if (!$entity) {
		    throw $this->createNotFoundException('Unable to find SiteContent entity.');
	    }
	    $em->remove($entity);
	    $em->flush();
        return $this->redirect($this->generateUrl('sitetestimonial'));
    }


}

<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\Branding;
use App\Tool\Form\BrandingType;

/**
 * Branding controller.
 *
 */
class BrandingController extends AbstractController
{

    /**
     * Lists all Branding entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository(\App\Tool\Entity\Branding::class)->findAll();

        return $this->render('Tool/Branding/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new Branding entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Branding();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->upload();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('branding_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/Branding/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Branding entity.
     *
     * @param Branding $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Branding $entity)
    {
        $form = $this->createForm(new BrandingType(), $entity, [
            'action' => $this->generateUrl('branding_create'),
            'method' => 'POST',
            'attr' => [
            'class' => 'horizontal-form',
            'novalidate' => 'novalidate',
        ]
        ]);

        return $form;
    }

    /**
     * Displays a form to create a new Branding entity.
     *
     */
    public function newAction()
    {
        $entity = new Branding();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/Branding/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Branding entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\Branding::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Branding entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Branding/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Branding entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\Branding::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Branding entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Branding/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Branding entity.
     *
     * @param Branding $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Branding $entity)
    {
        $form = $this->createForm(new BrandingType(), $entity, [
            'action' => $this->generateUrl('branding_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Branding entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\Branding::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Branding entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $entity->upload();
            $em->flush();

            return $this->redirect($this->generateUrl('branding_edit', ['id' => $id]));
        }

        return $this->render('Tool/Branding/edit.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a Branding entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(\App\Tool\Entity\Branding::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Branding entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('branding'));
    }

    /**
     * Creates a form to delete a Branding entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('branding_delete', ['id' => $id]))
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

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Tool\Entity\Branding::class)->find($id);

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
        $this->get('session')->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('branding'));
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $item = $this->getDoctrine()->getRepository(\App\Tool\Entity\Branding::class)->searchAutoComplete($item);
        }
        return new JsonResponse($item);
    }

    public function searchBrandNameAction($brand)
    {
        return new JsonResponse([
            'id'=>$brand,
            'text'=>$brand
        ]);
    }

}

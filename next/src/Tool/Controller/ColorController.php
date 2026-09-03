<?php

namespace App\Tool\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use App\Tool\Entity\ProductColor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Tool\Form\ColorType;

/**
 * ProductColor controller.
 *
 */
class ColorController extends AbstractController
{

    /**
     * Lists all ProductColor entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository(\App\Tool\Entity\ProductColor::class)->findBy(['isValid'=> 1],['name'=>'asc']);
        return $this->render('Tool/Color/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ProductColor entity.
     *
     */

    public function createAction(Request $request)
    {
        $entity = new ProductColor();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setStatus(false);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('color'));
        }

        return $this->render('Tool/Color/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ProductColor entity.
     *
     * @param ProductColor $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ProductColor $entity)
    {
        $form = $this->createForm(new ColorType(), $entity, [
            'action' => $this->generateUrl('color_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ProductColor entity.
     *
     */
    public function newAction()
    {
        $entity = new ProductColor();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/Color/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ProductColor entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\ProductColor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ProductColor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Color/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing ProductColor entity.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\ProductColor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ProductColor entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Color/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ProductColor entity.
     *
     * @param ProductColor $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ProductColor $entity)
    {
        $form = $this->createForm(new ColorType(), $entity, [
            'action' => $this->generateUrl('color_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing ProductColor entity.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\ProductColor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ProductColor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('color_edit', ['id' => $id]));
        }

        return $this->render('Tool/Color/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a ProductColor entity.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAction(ProductColor $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
        }

        try {

            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->get('session')->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->get('session')->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('ProductColor'));
    }

    /**
     * Creates a form to delete a ProductColor entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('color_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $item = $this->getDoctrine()->getRepository('SettingToolBundle:Color')->searchAutoComplete($item);
        }
        return new JsonResponse($item);
    }

    public function searchProductColorNameAction($color)
    {
        return new JsonResponse([
            'id'=>$color,
            'text'=>$color
        ]);
    }
}

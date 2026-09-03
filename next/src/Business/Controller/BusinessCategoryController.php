<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Business\Form\CategoryType;
use App\Business\Entity\Category;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * BusinessCategory controller.
 *
 */
class BusinessCategoryController extends AbstractController
{

    /**
     * Lists all BusinessCategory entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {

        $entity = new Category();
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/BusinessCategory/index.html.twig', [
            'entity' => $entity,
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);


    }


    /**
     * Creates a new BusinessCategory entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = new Category();
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entity->setBusinessConfig($globalOption->getBusinessConfig());
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_category', ['id' => $entity->getId()]));
        }

        return $this->render('Business/BusinessCategory/index.html.twig', [
            'entity' => $entity,
            'entities'      => $entities,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a BusinessCategory entity.
     *
     * @param BusinessCategory $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Category $entity)
    {

        $form = $this->createForm(new CategoryType(), $entity, [
            'action' => $this->generateUrl('business_category_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }



    /**
     * Displays a form to edit an existing BusinessCategory entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = $em->getRepository(\App\Business\Entity\Category::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessCategory entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('Business/BusinessCategory/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a BusinessCategory entity.
     *
     * @param BusinessCategory $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Category $entity )
    {

        $form = $this->createForm(new CategoryType(), $entity, [
            'action' => $this->generateUrl('business_category_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }
    /**
     * Edits an existing BusinessCategory entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $entity = $em->getRepository(\App\Business\Entity\Category::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessCategory entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_category'));
        }

        return $this->render('Business/BusinessCategory/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a BusinessCategory entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(Category $entity)
    {
        $em = $this->getDoctrine()->getManager();
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
        return $this->redirect($this->generateUrl('business_category'));
    }

    public function sortedAction(Request $request)
    {
        $data = $request ->request->get('item');
        $this->getDoctrine()->getRepository(\App\Business\Entity\Category::class)->setCategorySorting($data);
        exit;
    }


}

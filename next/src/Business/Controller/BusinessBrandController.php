<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Business\Entity\BusinessBrand;
use App\Business\Form\BusinessBrandType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * BusinessBrand controller.
 *u
 */
class BusinessBrandController extends AbstractController
{

    /**
     * Lists all BusinessBrand entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {

        $entity = new BusinessBrand();
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessBrand::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/BusinessBrand/index.html.twig', [
            'entity' => $entity,
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);


    }


    /**
     * Creates a new BusinessBrand entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessBrand::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = new BusinessBrand();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity->setBusinessConfig($option);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_brand', ['id' => $entity->getId()]));
        }
        return $this->render('Business/BusinessBrand/index.html.twig', [
            'entity' => $entity,
            'entities'      => $entities,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a BusinessBrand entity.
     *
     * @param BusinessBrand $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(BusinessBrand $entity)
    {

        $form = $this->createForm(new BusinessBrandType(), $entity, [
            'action' => $this->generateUrl('business_brand_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }



    /**
     * Displays a form to edit an existing BusinessBrand entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessBrand::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = $em->getRepository(\App\Business\Entity\BusinessBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessBrand entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('Business/BusinessBrand/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a BusinessBrand entity.
     *
     * @param BusinessBrand $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(BusinessBrand $entity )
    {

        $form = $this->createForm(new BusinessBrandType(), $entity, [
            'action' => $this->generateUrl('business_brand_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }
    /**
     * Edits an existing BusinessBrand entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessBrand::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $entity = $em->getRepository(\App\Business\Entity\BusinessBrand::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessBrand entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_brand'));
        }

        return $this->render('Business/BusinessBrand/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a BusinessBrand entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(BusinessBrand $entity)
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
        return $this->redirect($this->generateUrl('business_brand'));
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('business_brand'));
    }


}

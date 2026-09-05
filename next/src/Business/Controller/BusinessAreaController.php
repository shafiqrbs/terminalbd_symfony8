<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessArea;
use App\Business\Form\BusinessAreaType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * BusinessArea controller.
 *u
 */
class BusinessAreaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all BusinessArea entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {

        $entity = new BusinessArea();
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(array('businessConfig' => $option),array( 'name' =>'asc' ));
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/BusinessArea/index.html.twig', array(
            'entity' => $entity,
            'entities' => $entities,
            'form'   => $form->createView(),
        ));


    }


    /**
     * Creates a new BusinessArea entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(array('businessConfig' => $option),array( 'name' =>'asc' ));

        $entity = new BusinessArea();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setBusinessConfig($option);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_area', array('id' => $entity->getId())));
        }
        return $this->render('Business/BusinessArea/index.html.twig', array(
            'entity' => $entity,
            'entities'      => $entities,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a BusinessArea entity.
     *
     * @param BusinessArea $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessArea $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(BusinessAreaType::class, $entity, array('action' => $this->generateUrl('business_area_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'location' => $location));
        return $form;
    }



    /**
     * Displays a form to edit an existing BusinessArea entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(array('businessConfig' => $option),array( 'name' =>'asc' ));

        $entity = $em->getRepository(\App\Business\Entity\BusinessArea::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessArea entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('Business/BusinessArea/index.html.twig', array(
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a BusinessArea entity.
     *
     * @param BusinessArea $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(BusinessArea $entity )
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(BusinessAreaType::class, $entity, array('action' => $this->generateUrl('business_area_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'location' => $location));


        return $form;
    }
    /**
     * Edits an existing BusinessArea entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(array('businessConfig' => $option),array( 'name' =>'asc' ));
        $entity = $em->getRepository(\App\Business\Entity\BusinessArea::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessArea entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_area'));
        }

        return $this->render('Business/BusinessArea/index.html.twig', array(
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a BusinessArea entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(BusinessArea $entity)
    {
        $em = $this->em;
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('business_area'));
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessArea::class)->find($id);

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
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('business_area'));
    }


}

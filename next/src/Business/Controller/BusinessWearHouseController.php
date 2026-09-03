<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Business\Form\WearHouseType;
use App\Business\Entity\WearHouse;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * WearHouse controller.
 *
 */
class BusinessWearHouseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all WearHouse entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {

        $entity = new WearHouse();
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\WearHouse::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/WearHouse/index.html.twig', [
            'entity' => $entity,
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);


    }


    /**
     * Creates a new WearHouse entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\WearHouse::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = new WearHouse();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entity->setBusinessConfig($option);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_wearhouse', ['id' => $entity->getId()]));
        }

        return $this->render('Business/WearHouse/index.html.twig', [
            'entity' => $entity,
            'entities'      => $entities,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a WearHouse entity.
     *
     * @param WearHouse $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(WearHouse $entity)
    {

        $form = $this->createForm(new WearHouseType(), $entity, [
            'action' => $this->generateUrl('business_wearhouse_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }



    /**
     * Displays a form to edit an existing WearHouse entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\WearHouse::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = $em->getRepository(\App\Business\Entity\WearHouse::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WearHouse entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('Business/WearHouse/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a WearHouse entity.
     *
     * @param WearHouse $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(WearHouse $entity )
    {

        $form = $this->createForm(new WearHouseType(), $entity, [
            'action' => $this->generateUrl('business_wearhouse_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }
    /**
     * Edits an existing WearHouse entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\WearHouse::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $entity = $em->getRepository(\App\Business\Entity\WearHouse::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WearHouse entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_wearhouse'));
        }

        return $this->render('Business/WearHouse/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a WearHouse entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(WearHouse $entity)
    {
        $em = $this->em;
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('business_wearhouse'));
    }

    public function sortedAction(Request $request)
    {
        $data = $request ->request->get('item');
        $this->em->getRepository(\App\Business\Entity\WearHouse::class)->setWearHouseSorting($data);
        exit;
    }


}

<?php

namespace App\Hospital\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Hospital\Entity\HmsServiceGroup;
use App\Hospital\Form\ServiceGroupType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;


/**
 * CabinController controller.
 *
 */
class ServiceGroupController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }



    /**
     * Lists all HmsServiceGroup entities.
     *
     */
    public function indexAction()
    {

        $entity = new HmsServiceGroup();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->findBy(['hospitalConfig' => $config],['name'=>'ASC']);
        $form = $this->createCreateForm($entity);
        return $this->render('Hospital/ServiceGroup/index.html.twig', [
            'pagination' => $pagination,
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);

    }

    /**
     * Creates a new HmsServiceGroup entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new HmsServiceGroup();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->findBy(['hospitalConfig' => $config,'service'=>4],['name'=>'ASC']);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setHospitalConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('hms_service_group', ['id' => $entity->getId()]));
        }

        return $this->render('Hospital/ServiceGroup/index.html.twig', [
            'entity' => $entity,
            'pagination' => $pagination,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a HmsServiceGroup entity.
     *
     * @param HmsServiceGroup $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(HmsServiceGroup $entity)
    {
        $form = $this->createForm(new ServiceGroupType(), $entity, [
            'action' => $this->generateUrl('hms_service_group_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }


    /**
     * Displays a form to edit an existing HmsServiceGroup entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->findBy(['hospitalConfig' => $config],['name'=>'ASC']);
        $entity = $em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HmsServiceGroup entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Hospital/ServiceGroup/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a HmsServiceGroup entity.
     *
     * @param HmsServiceGroup $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(HmsServiceGroup $entity)
    {
        $form = $this->createForm(new ServiceGroupType(), $entity, [
            'action' => $this->generateUrl('hms_service_group_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing HmsServiceGroup entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->findBy(['hospitalConfig' => $config],['name'=>'ASC']);
        $entity = $em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HmsServiceGroup entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('hms_service_group'));
        }

        return $this->render('Hospital/ServiceGroup/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a HmsServiceGroup entity.
     *
     */
    public function deleteAction(HmsServiceGroup $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HmsServiceGroup entity.');
        }
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
        return $this->redirect($this->generateUrl('hms_service_group'));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(HmsServiceGroup $entity)
    {

        $em = $this->em;
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
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('hms_service_group'));
    }
}

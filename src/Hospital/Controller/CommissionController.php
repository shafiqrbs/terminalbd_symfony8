<?php

namespace App\Hospital\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Hospital\Entity\HmsCommission;
use App\Hospital\Form\CommissionType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Commission controller.
 *
 */
class CommissionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Vendor entities.
     *
     */
    public function indexAction()
    {

        $entity = new HmsCommission();
        $form   = $this->createCreateForm($entity);
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\HmsCommission::class)->findBy(['hospitalConfig' => $hospital],['name'=>'ASC']);
        return $this->render('Hospital/Commission/index.html.twig', [
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }
    /**
     * Creates a new Vendor entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new HmsCommission();
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\HmsCommission::class)->findBy(['hospitalConfig' => $hospital],['name'=>'ASC']);

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
            $entity->setHospitalConfig($hospital);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('hms_commission', ['id' => $entity->getId()]));
        }

        return $this->render('Hospital/Commission/index.html.twig', [
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Vendor entity.
     *
     * @param Vendor $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(HmsCommission $entity)
    {
        $form = $this->createForm(CommissionType::class, $entity, [
            'action' => $this->generateUrl('hms_commission_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to edit an existing Vendor entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\HmsCommission::class)->findBy(['hospitalConfig' => $hospital],['name'=>'ASC']);

        $entity = $em->getRepository(\App\Hospital\Entity\HmsCommission::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Commission entity.');
        }

        $editForm = $this->createEditForm($entity);


        return $this->render('Hospital/Commission/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Vendor entity.
     *
     * @param Vendor $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(HmsCommission $entity)
    {
        $form = $this->createForm(CommissionType::class, $entity, [
            'action' => $this->generateUrl('hms_commission_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Vendor entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\HmsCommission::class)->findBy(['hospitalConfig' => $hospital],['name'=>'ASC']);

        $entity = $em->getRepository(\App\Hospital\Entity\HmsCommission::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('hms_commission'));
        }

        return $this->render('Hospital/Commission/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Hospital\Entity\HmsCommission::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
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
        return $this->redirect($this->generateUrl('hms_commission'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Hospital\Entity\HmsCommission::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find commission entity.');
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
        return $this->redirect($this->generateUrl('hms_commission'));
    }



}

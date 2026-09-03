<?php

namespace App\DoctorPrescription\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\DoctorPrescription\Entity\DpsParticular;
use App\DoctorPrescription\Entity\Particular;
use App\DoctorPrescription\Form\DiseasesType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;


/**
 * ParticularController controller.
 *
 */
class DiseasesController extends AbstractController
{

    public function paginate($entities)
    {
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            50  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all Particular entities.
     *
     */
    public function indexAction()
    {
        $entity = new DpsParticular();
        $data = $_REQUEST;
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getFindDentalServiceParticular($config , ['diseases']);
        $editForm = $this->createCreateForm($entity);
        return $this->render('DoctorPrescription/Diseases/index.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $data,
            'form'   => $editForm->createView(),
        ]);

    }

    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new DpsParticular();
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getServiceLists($config);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setDpsConfig($config);
            $entity->setMode('diseases');
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('dps_diseases'));
        }

        return $this->render('DoctorPrescription/Diseases/index.html.twig', [
            'entity' => $entity,
            'pagination' => $pagination,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param DpsParticular $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(DpsParticular $entity)
    {
        $form = $this->createForm(new DiseasesType(), $entity, [
            'action' => $this->generateUrl('dps_diseases_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }



    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getFindDentalServiceParticular($config , ['diseases']);
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('DoctorPrescription/Diseases/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(DpsParticular $entity)
    {


        $form = $this->createForm(new DiseasesType(), $entity, [
            'action' => $this->generateUrl('dps_diseases_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getServiceLists($config);
        //$pagination = $this->paginate($pagination);
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('dps_diseases'));
        }

        return $this->render('DoctorPrescription/Diseases/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction(DpsParticular $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
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
        return $this->redirect($this->generateUrl('dps_diseases'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(DpsParticular $entity)
    {

        $em = $this->getDoctrine()->getManager();
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
        return $this->redirect($this->generateUrl('dps_diseases'));
    }
}

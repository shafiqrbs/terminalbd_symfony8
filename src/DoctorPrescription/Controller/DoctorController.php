<?php

namespace App\DoctorPrescription\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DoctorPrescription\Entity\DpsParticular;
use App\DoctorPrescription\Form\DoctorType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * DoctorController controller.
 *
 */
class DoctorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {
        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            50  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all DpsParticular entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getFindDentalServiceParticular($config , array('doctor'));
        return $this->render('DoctorPrescription/Doctor/index.html.twig', array(
            'entities' => $pagination,
            'categories' => '',
            'departments' => '',
            'searchForm' => $data,
        ));

    }

    /**
     * Creates a new DpsParticular entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new DpsParticular();
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createCreateForm($entity,$globalOption);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setDpsConfig($globalOption ->getDpsConfig());
            $entity->setMode('doctor');
            $entity->setName($entity->getAssignDoctor()->getProfile()->getName());
            $entity->setMobile($entity->getAssignDoctor()->getProfile()->getMobile());
            if(empty($entity->getDesignation())){
                $entity->setDesignation($entity->getAssignDoctor()->getProfile()->getDesignation()->getName());
            }
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('dps_doctor_new', array('id' => $entity->getId())));
        }

        return $this->render('DoctorPrescription/Doctor/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a DpsParticular entity.
     *
     * @param DpsParticular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(DpsParticular $entity, $globalOption)
    {

        $form = $this->createForm(DoctorType::class, $entity, array('action' => $this->generateUrl('dps_doctor_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

    /**
     * Displays a form to create a new DpsParticular entity.
     *
     */
    public function newAction()
    {
        $entity = new DpsParticular();
        $globalOption = $this->getUser()->getGlobalOption();
        $form   = $this->createCreateForm($entity,$globalOption);

        return $this->render('DoctorPrescription/Doctor/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a DpsParticular entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DpsParticular entity.');
        }
        return $this->render('DoctorPrescription/Doctor/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing DpsParticular entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->find($id);

        if (!$entity and $this->getUser()->getDpsConfig()->getId() == $entity ->getDpsConfig()->getId()) {
            throw $this->createNotFoundException('Unable to find DpsParticular entity.');
        }
        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);

        return $this->render('DoctorPrescription/Doctor/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a DpsParticular entity.
     *
     * @param DpsParticular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(DpsParticular $entity,$globalOption)
    {

        $form = $this->createForm(DoctorType::class, $entity, array('action' => $this->generateUrl('dps_doctor_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));


        return $form;
    }
    /**
     * Edits an existing DpsParticular entity.
     *
     */
    public function updateAction(Request $request, DpsParticular $entity)
    {
        $em = $this->em;
        if (!$entity and $this->getUser()->getDpsConfig()->getId() == $entity ->getDpsConfig()->getId()) {
            throw $this->createNotFoundException('Unable to find DpsParticular entity.');
        }

        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->setName($entity->getAssignDoctor()->getProfile()->getName());
            $entity->setMobile($entity->getAssignDoctor()->getProfile()->getMobile());
            if(empty($entity->getDesignation())){
                $entity->setDesignation($entity->getAssignDoctor()->getProfile()->getDesignation()->getName());
            }
            if($entity->upload()){
                $entity->removeUpload();
                $entity->upload();
            }else{
                $entity->upload();
            }
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('dps_doctor_edit',array('id' => $entity->getId())));
        }

        return $this->render('DoctorPrescription/Doctor/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a DpsParticular entity.
     *
     */
    public function deleteAction(DpsParticular $entity)
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
        return $this->redirect($this->generateUrl('dps_doctor'));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(DpsParticular $entity)
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
        return $this->redirect($this->generateUrl('dps_doctor'));
    }
}

<?php

namespace App\DoctorPrescription\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\DoctorPrescription\Entity\DpsService;
use App\DoctorPrescription\Form\ServiceType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;


/**
 * ParticularController controller.
 *
 */
class ServiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }



    /**
     * Lists all Particular entities.
     *
     */
    public function indexAction()
    {
        $entity = new DpsService();
        $data = $_REQUEST;
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->getServiceLists($config);
        $editForm = $this->createCreateForm($entity);
        return $this->render('DoctorPrescription/Service/index.html.twig', [
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
        $entity = new DpsService();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->getServiceLists($config);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setDpsConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('dps_service'));
        }

        return $this->render('DoctorPrescription/Service/index.html.twig', [
            'entity' => $entity,
            'pagination' => $pagination,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param DpsService $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(DpsService $entity)
    {
        $form = $this->createForm(new ServiceType(), $entity, [
            'action' => $this->generateUrl('dps_service_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
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
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->getServiceLists($config);
        //$pagination = $this->paginate($pagination);
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('DoctorPrescription/Service/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param DpsService $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(DpsService $entity)
    {

        $form = $this->createForm(new ServiceType(), $entity, [
            'action' => $this->generateUrl('dps_service_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
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
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->getServiceLists($config);
        //$pagination = $this->paginate($pagination);
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->setSlug($entity->getName());
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('dps_service'));
        }
        $pagination = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->getServiceLists($config);
        return $this->render('DoctorPrescription/Service/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction(DpsService $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
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
        return $this->redirect($this->generateUrl('dps_service'));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(DpsService $entity)
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
        return $this->redirect($this->generateUrl('dps_service:'));
    }

    /**
     * Lists all FeatureWidget entities.
     *
     */
    public function sortedAction(Request $request,$fieldName)
    {
        $data = $request ->request->get('item');
        $this->em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->setListOrdering($fieldName,$data);
        exit;
    }

}

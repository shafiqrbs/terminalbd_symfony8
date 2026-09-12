<?php

namespace App\Hospital\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\DoctorType;
use App\Hospital\Form\CabinType;
use App\Hospital\Form\ParticularType;
use App\Hospital\Form\PathologyType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * CabinController controller.
 *
 */
class CabinController extends AbstractController
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
        $entity = new Particular();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\Particular::class)->findBy(array('hospitalConfig' => $config,'service'=> 2),array('name'=>'ASC'));
        $form = $this->createCreateForm($entity);
        return $this->render('Hospital/Cabin/index.html.twig', array(
            'pagination' => $pagination,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));

    }


    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Particular();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\Particular::class)->findBy(array('hospitalConfig' => $config,'service'=>2),array('name'=>'ASC'));

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setHospitalConfig($config);
            $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(2);
            $entity->setService($service);
            $name = $entity->getServiceGroup()->getName().'-'.$entity->getRoom();
            $entity->setName($name);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('hms_cabin', array('id' => $entity->getId())));
        }

        return $this->render('Hospital/Cabin/index.html.twig', array(
            'entity' => $entity,
            'pagination' => $pagination,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Particular $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(CabinType::class, $entity, array('action' => $this->generateUrl('hms_cabin_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }


    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\Particular::class)->findBy(array('hospitalConfig' => $config,'service'=> 2),array('name'=>'ASC'));
        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Hospital/Cabin/index.html.twig', array(
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Particular $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(CabinType::class, $entity, array('action' => $this->generateUrl('hms_cabin_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }
    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Hospital\Entity\Particular::class)->findBy(array('hospitalConfig' => $config,'service'=> 2),array('name'=>'ASC'));
        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $name = $entity->getServiceGroup()->getName().'-'.$entity->getRoom();
            $entity->setName($name);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('hms_cabin'));
        }

        return $this->render('Hospital/Cabin/index.html.twig', array(
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction(Particular $entity)
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

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('hms_cabin'));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Particular $entity)
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
        return $this->redirect($this->generateUrl('hms_cabin'));
    }
}

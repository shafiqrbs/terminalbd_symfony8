<?php

namespace App\Hospital\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\DoctorType;
use App\Hospital\Form\ReferredDoctorType;
use App\Hospital\Form\ParticularType;
use App\Hospital\Form\PathologyType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * ReferredDoctorController controller.
 *
 */
class ReferredDoctorController extends AbstractController
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
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }


    /**
     * Lists all Particular entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\Particular::class)->findWithSearch($config , $service = 6, $data);
        $pagination = $this->paginate($entities);
        $categories = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(array('parent'=>2),array('name' =>'asc' ));
        $departments = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(array('parent'=>7),array('name' =>'asc' ));
        return $this->render('Hospital/ReferredDoctor/index.html.twig', array(
            'entities' => $pagination,
            'categories' => $categories,
            'departments' => $departments,
            'searchForm' => $data,
        ));

    }

    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Particular();
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createCreateForm($entity,$globalOption);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity->setHospitalConfig($globalOption -> getHospitalConfig());
            $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(6);
            $entity->setService($service);
            if($entity->getParticularCode()){
                $entity->setParticularCode($entity->getParticularCode());
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('hms_referreddoctor_new', array('id' => $entity->getId())));
        }

        return $this->render('Hospital/ReferredDoctor/new.html.twig', array(
            'entity' => $entity,
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
    private function createCreateForm(Particular $entity, $globalOption)
    {

        $em = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(ReferredDoctorType::class, $entity, array('action' => $this->generateUrl('hms_referreddoctor_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'emCategory' => $em, 'globalOption' => $globalOption, 'location' => $location));
        return $form;
    }

    /**
     * Displays a form to create a new Particular entity.
     *
     */
    public function newAction()
    {
        $entity = new Particular();
        $globalOption = $this->getUser()->getGlobalOption();
        $form   = $this->createCreateForm($entity,$globalOption);

        return $this->render('Hospital/ReferredDoctor/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Particular entity.
     *
     */
    public function showAction($id)
    {

    }

    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);

        return $this->render('Hospital/ReferredDoctor/new.html.twig', array(
            'entity'      => $entity,
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
    private function createEditForm(Particular $entity,$globalOption)
    {
        $em = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(ReferredDoctorType::class, $entity, array('action' => $this->generateUrl('hms_referreddoctor_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'emCategory' => $em, 'globalOption' => $globalOption, 'location' => $location));
        return $form;
    }
    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if($entity->getParticularCode()){
                $entity->setParticularCode($entity->getParticularCode());
            }
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('hms_referreddoctor'));
        }

        return $this->render('Hospital/ReferredDoctor/new.html.twig', array(
            'entity'      => $entity,
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
        return $this->redirect($this->generateUrl('hms_referreddoctor'));
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
        return $this->redirect($this->generateUrl('hms_referreddoctor'));
    }
}

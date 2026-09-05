<?php

namespace App\Hospital\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\HmsMasterDiagnosticReport;
use App\Hospital\Entity\HmsMasterDiagnosticReportFormat;
use App\Hospital\Entity\Particular;
use App\Hospital\Entity\PathologicalReport;
use App\Hospital\Form\PathologyType;
use App\Hospital\Entity\DiagnosticReport;
use App\Hospital\Entity\DiagnosticReportFormat;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


/**
 * Pathology controller.
 *
 */
class PathologyController extends AbstractController
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
        $entities = $em->getRepository(\App\Hospital\Entity\Particular::class)->findWithSearch($config , $service = 1, $data);
        $pagination = $this->paginate($entities);
        $categories = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(array('parent'=>2),array('name' =>'asc' ));
        $departments = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(array('parent'=>7),array('name' =>'asc' ));
        return $this->render('Hospital/Pathology/index.html.twig', array(
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

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setHospitalConfig($globalOption -> getHospitalConfig());
            $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(1);
            $entity->setService($service);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('hms_pathology_new', array('id' => $entity->getId())));
        }

        return $this->render('Hospital/Pathology/new.html.twig', array(
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
        $form = $this->createForm(PathologyType::class, $entity, array('action' => $this->generateUrl('hms_pathology_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'emCategory' => $em, 'globalOption' => $globalOption));
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

        return $this->render('Hospital/Pathology/new.html.twig', array(
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

        return $this->render('Hospital/Pathology/new.html.twig', array(
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
        $form = $this->createForm(PathologyType::class, $entity, array('action' => $this->generateUrl('hms_pathology_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'emCategory' => $em, 'globalOption' => $globalOption));


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

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('hms_pathology_edit',array('id' => $entity->getId())));
        }

        return $this->render('Hospital/Pathology/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
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
        return $this->redirect($this->generateUrl('hms_pathology'));
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
        return $this->redirect($this->generateUrl('hms_pathology'));
    }

    public function copyToMedicineStockAction()
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $option = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($_REQUEST['option']);
        $config = $option->getMedicineConfig();
        if($option and !empty($config)) {
            $fromConfig = $config->getId();
            $toConfig = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->processStockMigration($fromConfig,$toConfig);
            return new Response('success');
        }
        return new Response('failed');
    }

    public function resetPathologyTestWithReportAction()
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $option = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($_REQUEST['option']);
        $toConfig = $option->getHospitalConfig();
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        if($option and !empty($config)) {
            $stock = $em->createQuery("DELETE HospitalBundle:Particular e WHERE e.hospitalConfig={$config->getId()}");
            if($stock){
                $stock->execute();
            }
            $ent = $em->getRepository(\App\Hospital\Entity\Particular::class)->findWithSearch($toConfig , $service = 1,$data='');
            $entities = $ent->getQuery()->getResult();
            /* @var $entity Particular */
            foreach ($entities as $entity) {
                $exist = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findOneBy(array('hospitalConfig' => $config, 'oldReportId' => $entity->getId()));
                if (empty($exist)) {
                    $pathological = new Particular();
                    $pathological->setHospitalConfig($config);
                    $pathological->setOldReportId($entity->getId());
                    $pathological->setCode($entity->getCode());
                    $pathological->setName($entity->getName());
                    if ($entity->getCategory()) {
                        $pathological->setCategory($entity->getCategory());
                    }
                    if ($entity->getDepartment()) {
                        $pathological->setDepartment($entity->getDepartment());
                    }
                    $pathological->setStatus(1);
                    $pathological->setPrice($entity->getPrice());
                    $pathological->setService($entity->getService());
                    $pathological->setSepcimen($entity->getSepcimen());
                    $pathological->setInstruction($entity->getInstruction());
                    $pathological->setTestDuration($entity->getTestDuration());
                    $pathological->setReportFormat($entity->getReportFormat());
                    $em->persist($pathological);
                    $em->flush();
                    $this->insertMasterReport($entity, $pathological);
                }
            }
        }
        return $this->redirect($this->generateUrl('hms_pathology'));
    }

    public function insertMasterReport(Particular $entity,Particular $pathological)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        foreach ($entity->getPathologicalReports() as $report){
            $format = New PathologicalReport();
            $format->setParticular($pathological);
            $format->setParent($report->getParent());
            $format->setName($report->getName());
            $format->setReferenceValue($report->getReferenceValue());
            $format->setUnit($report->getUnit());
            $format->setSorting($report->getSorting());
            $format->setStatus(1);
            $em->persist($format);
            $em->flush();
        }
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        /* @var $entity Particular */
        $entity = $em->getRepository(Particular::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        $setField = 'set' . $data['name'];
        $entity->$setField($data['value']);
        $em->flush();
        return new Response('success');

    }

}

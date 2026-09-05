<?php

namespace App\Hospital\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\Particular;
use App\Hospital\Entity\PathologicalReport;
use App\Hospital\Form\PathologicalReportType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


/**
 * Pathology controller.
 *
 */
class PathologicalReportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }




    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function pathologicalReportAction(Particular $pathology)
    {
        $hmsc = $this->getUser()->getGlobalOption()->getHospitalConfig();
        if($hmsc->getId() == $pathology->getHospitalConfig()->getId()){
            $em = $this->em;
            $entity = New PathologicalReport();
            $form = $this->createCreateForm($entity,$pathology);
            $reportFormats = $this->em->getRepository(\App\Hospital\Entity\PathologicalReport::class)->findBy(array('particular' => $pathology),array('sorting'=>'asc','parent'=>'asc'));
            return $this->render('Hospital/Pathology/pathologicalReport.html.twig', array(
                'entity'        => $entity,
                'pathology'     => $pathology,
                'reportFormats'     => $reportFormats,
                'form'          => $form->createView(),
            ));
        }else{
            throw $this->createNotFoundException('Unable to find wrong access.');
        }


    }

    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request,Particular $pathology)
    {
        $entity = new PathologicalReport();
        $form = $this->createCreateForm($entity,$pathology);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity->setParticular($pathology);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('hms_pathological_report', array('pathology' => $pathology->getId())));
        }

        return $this->render('Hospital/Pathology/new.html.twig', array(
            'entity'        => $entity,
            'pathology'     => $pathology,
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
    private function createCreateForm(PathologicalReport $entity, Particular $pathology)
    {

        $form = $this->createForm(PathologicalReportType::class, $entity, array('action' => $this->generateUrl('hms_pathological_report_create', array('pathology' => $pathology->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'particular' => $pathology));
        return $form;
    }

    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function editAction(Particular $pathology,PathologicalReport $entity)
    {
        $em = $this->em;
        $form = $this->createEditForm($entity,$pathology);
        return $this->render('Hospital/Pathology/pathologicalReport.html.twig', array(
            'entity'        => $entity,
            'pathology'     => $pathology,
            'form'          => $form->createView(),
        ));
    }



    /**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(PathologicalReport $entity,Particular $pathology)
    {
        $form = $this->createForm(PathologicalReportType::class, $entity, array('action' => $this->generateUrl('hms_pathological_report_update', array('pathology' => $pathology->getId(),'id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'particular' => $pathology));
        return $form;
    }


    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, Particular $pathology, PathologicalReport $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $editForm = $this->createEditForm($entity,$pathology);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('hms_pathological_report',array('pathology'=>$pathology->getId())));
        }

        return $this->render('Hospital/Pathology/pathologicalReport.html.twig', array(
            'entity'      => $entity,
            'pathology'     => $pathology,
            'form'   => $editForm->createView(),
        ));
    }


    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction(Particular $pathology, PathologicalReport $pathologicalReport)
    {

        $em = $this->em;
        if (!$pathologicalReport) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        try {

            $em->remove($pathologicalReport);
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
        return $this->redirect($this->generateUrl('hms_pathological_report',array('pathology'=>$pathology->getId())));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Particular $pathology , PathologicalReport $entity)
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
        return $this->redirect($this->generateUrl('hms_pathological_report',array('pathology' => $pathology->getId())));
    }

    public function sortingAction(Request $request,Particular $pathology)
    {
        $data = $request ->request->get('item');
        $this->em->getRepository(\App\Hospital\Entity\PathologicalReport::class)->updateSorting($data);
        exit;
    }

    public function inlineReportUpdateAction(Request $request)
    {
        $data = $request->request->all();

        $em = $this->em;
        /* @var $entity PathologicalReport */
        $entity = $this->em->getRepository(PathologicalReport::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PathologicalReport entity.');
        }
        $setField = 'set' . $data['name'];
        $entity->$setField($data['value']);
        $em->flush();
        return new Response('success');

    }
}

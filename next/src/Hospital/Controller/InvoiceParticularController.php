<?php

namespace App\Hospital\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceParticular;
use App\Hospital\Entity\InvoicePathologicalGroup;
use App\Hospital\Entity\InvoicePathologicalReport;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\InvoiceGroupParticularType;
use App\Hospital\Form\InvoiceParticularType;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Hackzilla\BarcodeBundle\Utility\Barcode;
/**
 * InvoiceParticularcontroller.
 *
 */
class InvoiceParticularController extends Controller
{

    public function paginate($entities)
    {

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            50  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }




    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_COMMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();

        $entities = $em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->invoicePathologicalReportLists( $user, $data);
        $pagination = $this->paginate($entities);
        $particularService = $this->getDoctrine()->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->processPathologicalReports($hospital->getId());
        $assignDoctors = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(5));
        $referredDoctors = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(6));

        return $this->render('Hospital/InvoiceParticular/index.html.twig', array(
            'entities' => $pagination,
            'particularService' => $particularService,
            'assignDoctors' => $assignDoctors,
            'referredDoctors' => $referredDoctors,
            'searchForm' => $data,
        ));
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR')"))]
    public function reportProcessAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->invoicePathologicalReportLists( $user,$data);
        $pagination = $this->paginate($entities);
        $assignDoctors = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(5));
        $referredDoctors = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(6));
        $particularService = $this->getDoctrine()->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->processPathologicalReports($hospital->getId());
        $selected = explode(',', $request->cookies->get('barcodes', ''));
        return $this->render('Hospital/InvoiceParticular/diagnostic.html.twig', array(
            'entities' => $pagination,
            'particularService' => $particularService,
            'assignDoctors' => $assignDoctors,
            'selected' => $selected,
            'referredDoctors' => $referredDoctors,
            'searchForm' => $data,
        ));
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]

    public function patientTestAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $data[] = array('process'=>'In-progress');
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceLists( $user , $mode = 'diagnostic' , $data);
        $pagination = $this->paginate($entities);
        $employees = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital->getId());
        $processes = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'diagnostic',$data);
        $assignDoctors = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignProcess($hospital,'assign-doctor');
        $referredDoctors = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignProcess($hospital,'referred-doctor');
        return $this->render('Hospital/InvoiceParticular/patientTest.html.twig', array(
            'entities'                          => $pagination,
            'assignDoctors'                     => $assignDoctors,
            'referredDoctors'                   => $referredDoctors,
            'processes'                         => $processes,
            'employees'                         => $employees,
            'searchForm'                        => $data,
        ));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]

    public function patientGroupTestAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\InvoicePathologicalGroup::class)->invoicePathologicalGroupLists($user,$data);
        $pagination = $this->paginate($entities);
        $employees = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital->getId());
        $processes = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'diagnostic',$data);
        return $this->render('Hospital/InvoiceParticular/patientGroupTest.html.twig', array(
            'entities'                          => $pagination,
            'processes'                         => $processes,
            'employees'                         => $employees,
            'searchForm'                        => $data,
        ));

    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR')"))]

    public function groupPathologicalReportAction(Request $request, Invoice $invoice)
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $entity = new InvoicePathologicalGroup();
        $selected = explode(',', $request->cookies->get('barcodes', ''));

        $form = $this->createGroupReportForm($entity,$invoice->getId());
        $reportArr = array();

        /** @var InvoicePathologicalReport $row */
        $pathologies = [];
        $selected = (str_replace('on','',$selected));
        if(is_array($selected) and count($selected) > 0 and $selected[0] != "" ){
            foreach ($selected as $particular):
                $pathology = $this->getDoctrine()->getRepository(InvoiceParticular::class)->find($particular);
                $pathology->getParticular()->getService()->getSlug();
                if($pathology->getParticular()->getService()->getSlug() == 'diagnostic'){
                    $this->getDoctrine()->getRepository(\App\Hospital\Entity\InvoicePathologicalReport::class)->insertGroup($pathology);
                    $pathologies[] = $pathology;
                    if (!empty($pathology->getInvoicePathologicalReports())){
                        foreach ($pathology->getInvoicePathologicalReports() as $row):
                            if(!empty($row->getPathologicalReport())){
                                $reportArr[$row->getPathologicalReport()->getId()] = $row;
                            }
                        endforeach;
                    }
                }
            endforeach;
            if(empty($reportArr)) {
                unset($_COOKIE['barcodes']);
                setcookie('barcodes', '', time() - 3600, '/');
                return $this->redirect($this->generateUrl('hms_invoice_particular_show', array('id' => $invoice->getId())));
            }
            return $this->render('Hospital/InvoiceParticular/group-report.html.twig', array(
                'invoice' => $invoice,
                'entity' => $entity,
                'pathologies' => $pathologies,
                'report' => $reportArr,
                'form' => $form->createView(),
            ));
        }
        return $this->redirect($this->generateUrl('hms_invoice_particular_show', array('id' => $invoice->getId())));

    }

    public function groupPathologicalReportUpdateAction(InvoicePathologicalReport $entity)
    {
        $result = $_REQUEST['result'];
        $em = $this->getDoctrine()->getManager();
        $entity->setResult($result);
        $em->flush();
        return new Response('success');
    }


    public function showAction(Request $request,Invoice $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $selected = explode(',', $request->cookies->get('barcodes', ''));

        return $this->render('Hospital/InvoiceParticular/show.html.twig', array(
            'entity' => $entity,
            'selected' => $selected,
        ));
    }



    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR')"))]
    public function groupUpdateAction(Request $request, Invoice $invoice)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new InvoicePathologicalGroup();
        $selected = explode(',', $request->cookies->get('barcodes', ''));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $form = $this->createGroupReportForm($entity,$invoice->getId());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity->setHmsInvoice($invoice);
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success', "Report has been created successfully"
            );
            /* @var $pathology InvoiceParticular */

            foreach ($selected as $particular){
                $pathology = $this->getDoctrine()->getRepository(InvoiceParticular::class)->find($particular);
                if(empty($pathology->getInvoicePathologicalGroup())){
                    $pathology->setInvoicePathologicalGroup($entity);
                    $em->persist($pathology);
                    $em->flush();
                }
            }

            unset($_COOKIE['barcodes']);
            setcookie('barcodes', '', time() - 3600, '/');
            return $this->redirect($this->generateUrl('hms_invoice_particular_show', array('id' => $invoice->getId())));
        }
        $reportArr = array();

        /** @var InvoicePathologicalReport $row */

        $pathologies = [];
        foreach ($selected as $particular):
            $pathology = $this->getDoctrine()->getRepository(InvoiceParticular::class)->find($particular);
            if($pathology->getParticular()->getService()->getSlug() == 'diagnostic' and $pathology->getParticular()->getPathologicalReports()){
                $this->getDoctrine()->getRepository(\App\Hospital\Entity\InvoicePathologicalReport::class)->insertGroup($pathology);
                $pathologies[] = $pathology;
                if (!empty($pathology->getInvoicePathologicalReports())){
                    foreach ($pathology->getInvoicePathologicalReports() as $row):
                        if(!empty($row->getPathologicalReport())){
                            $reportArr[$row->getPathologicalReport()->getId()] = $row;
                        }
                    endforeach;
                }
            }
        endforeach;
        return $this->render('Hospital/InvoiceParticular/group-report.html.twig', array(
            'invoice' => $invoice,
            'entity' => $entity,
            'pathologies' => $pathologies,
            'report' => $reportArr,
            'form' => $form->createView(),
        ));
    }


    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param InvoicePathologicalGroup $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createGroupReportForm(InvoicePathologicalGroup $entity,$invoice)
    {
        $hospitalConfig = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $em = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsCategory::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InvoiceGroupParticularType::configureOptions() and pass them as form options: $hospitalConfig,$em), $entity, array(
        $form = $this->createForm(new InvoiceGroupParticularType($hospitalConfig,$em), $entity, array(
            'action' => $this->generateUrl('hms_pathological_group_report_update',['invoice' => $invoice]),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceParticular',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }



    public function sampleCollectionAction(Invoice $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity preparation.');
        }
        return $this->render('Hospital/InvoiceParticular/sampleCollection.html.twig', array(
            'entity' => $entity,
        ));
    }

    public function getBarcode($barcodePrint)
    {
       
        $barcode = new BarcodeGenerator();
        $barcode->setText($barcodePrint);
        $barcode->setType(BarcodeGenerator::Code128);
        $barcode->setScale(1);
        $barcode->setThickness(25);
        $barcode->setFontSize(10);
        $code = $barcode->generate();
        $data = '';
        $data .= '<img src="data:image/png;base64,'.$code .'" />';
        return $data;
    }

    public function sampleCollectionBarcodeAction(InvoiceParticular $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity preparation.');
        }
	   // $barcodePrint = $entity->getParticular()->getParticularCode().'-'.$entity->getReportCode();
	    $barcodePrint = $entity->getHmsInvoice()->getInvoice();
        $barcodeReport = $this->getBarcode($barcodePrint);
        return $this->render('Hospital/InvoiceParticular/sampleCollectionBarcode.html.twig', array(
            'entity' => $entity,
            'barcode' => $barcodeReport,
        ));
    }

    public function sampleCollectionConfirmAction(InvoiceParticular $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice Particular entity preparation.');
        }
        if (!empty($entity)) {
            $em = $this->getDoctrine()->getManager();
            $entity->setProcess('Collected');
            $entity->setSampleCollectedBy($this->getUser());
            $entity->setCollectionDate(new \DateTime());
            $em->flush();
        }
        exit;
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR')"))]

    public function machineFormatAction(InvoiceParticular $entity)
    {
        $em = $this->getDoctrine()->getManager();
        $entity->setParticularDeliveredBy($this->getUser());
        $entity->setParticularPreparedBy($this->getUser());
        $entity->setCollectionDate(new \DateTime());
        $entity->setProcess('Collected');
        $entity->setSampleCollectedBy($this->getUser());
        $entity->setProcess('Done');
        $em->persist($entity);
        $em->flush();
        return new Response('done');
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR')"))]

    public function preparationAction(InvoiceParticular $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity preparation.');
        }
        $form = $this->createEditForm($entity);

        /** @var  $reportArr */
        $reportArr = array();

        /** @var InvoicePathologicalReport $row */
        $em = $this->getDoctrine()->getManager();
        if (!empty($entity->getInvoicePathologicalReports())){
            foreach ($entity->getInvoicePathologicalReports() as $row):
                if(!empty($row->getPathologicalReport())){
                    $reportArr[$row->getPathologicalReport()->getId()] = $row;
                }
            endforeach;
        }
        return $this->render('Hospital/InvoiceParticular/new.html.twig', array(
            'entity' => $entity,
            'report' => $reportArr,
            'form' => $form->createView(),
        ));
    }



    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param Invoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(InvoiceParticular $entity)
    {

        $hospitalConfig = $this->getUser()->getGlobalOption()->getHospitalConfig();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InvoiceParticularType::configureOptions() and pass them as form options: $hospitalConfig), $entity, array(
        $form = $this->createForm(new InvoiceParticularType($hospitalConfig), $entity, array(
            'action' => $this->generateUrl('hms_invoice_particular_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $data = $request->request->all();

        if ($editForm->isValid()) {
            $entity->setParticularPreparedBy($this->getUser());
            $entity->upload();
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Hospital\Entity\InvoicePathologicalReport::class)->insert($entity,$data);
            $this->get('session')->getFlashBag()->add(
                'success',"Report has been created successfully"
            );
            return $this->redirect($this->generateUrl('hms_invoice_particular_preparation',array('id'=> $entity->getId())));
        }

        return $this->render('Hospital/Pathology/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }


    public function deliveryAction(InvoiceParticular $entity)
    {
        if (!empty($entity)) {
            $em = $this->getDoctrine()->getManager();
            $entity->setParticularDeliveredBy($this->getUser());
            $em->flush();
        }
        $invoice = $entity->getHmsInvoice();
        if($invoice->getPaymentStatus() == 'Paid' and $invoice->getReportCount() == $invoice->getDeliveryCount()){
            $em = $this->getDoctrine()->getManager();
            $invoice->setProcess('Done');
            $em->flush();
            $accountInvoice = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->insertHospitalFinalAccountInvoice($invoice);
           // $this->getDoctrine()->getRepository(\App\Accounting\Entity\Transaction::class)->hmsSalesFinal($invoice, $accountInvoice);
        }
        return $this->redirect($this->generateUrl('hms_invoice_confirm', array('id' => $entity->getHmsInvoice()->getId())));
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMIN')"))]

    public function reportReverseAction(InvoiceParticular $entity)
    {
        if (!empty($entity)) {
            $em = $this->getDoctrine()->getManager();
            $entity->setProcess('In-progress');
            $em->flush();
        }
        return $this->redirect($this->generateUrl('hms_invoice_particular_preparation',['id'=> $entity->getId()]));
    }

    public function pathologicalReportPrintAction(Request $request ,InvoiceParticular $entity)
    {
        /** @var  $reportArr */
        $reportArr = array();

        /** @var InvoicePathologicalReport $row */
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getHospitalConfig();
        if($config->getId() != $entity->getHmsInvoice()->getHospitalConfig()->getId()){
             $referer = $request->headers->get('referer');
             return new RedirectResponse($referer);
        }
        $barcodePrint = $entity->getHmsInvoice()->getInvoice();
        $barcodeInvoice = $this->getBarcode($barcodePrint);
        $barcodePrint = $entity->getParticular()->getParticularCode().'-'.$entity->getReportCode();
        $barcodeReport = $this->getBarcode($barcodePrint);

        if (!empty($entity->getInvoicePathologicalReports())){
            foreach ($entity->getInvoicePathologicalReports() as $row):
                if(!empty($row->getPathologicalReport())){
                    $reportArr[$row->getPathologicalReport()->getId()] = $row;
                }
            endforeach;
        }
        $hospital = $global->getHospitalConfig();
        if($hospital->isCustomPrint() == 1){
            $template = "Print/{$global->getId()}:pathological";
        }else{
            $template = "InvoiceParticular:pathologicalReportPrint";
        }
        return $this->render("HospitalBundle:{$template}.html.twig", array(
            'entity'            => $entity,
            'global'            => $global,
            'printUser'         => $this->getUser(),
            'barcodeInvoice'    => $barcodeInvoice,
            'barcodeReport'     => $barcodeReport,
            'report'            => $reportArr,
        ));
    }

    public function pathologicalReportDeleteAction(InvoicePathologicalReport $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new  JsonResponse('success');
        exit;

    }

    public function reportStatusSelectAction()
    {
        $items  = array();
        $items[]= array('value' => 'In-progress','text'=>'In-progress');
        $items[]= array('value' => 'Done','text'=>'Done');
        $items[]= array('value' => 'Damage','text'=>'Damage');
        $items[]= array('value' => 'Impossible','text'=>'Impossible');
        return new JsonResponse($items);
    }


    public function deliveryGroupAction(InvoicePathologicalGroup $entity)
    {

        $invoice = $entity->getHmsInvoice();
        if($invoice->getPaymentStatus() == 'Paid'){
            $em = $this->getDoctrine()->getManager();
            $entity->setParticularDeliveredBy($this->getUser());
            $entity->setProcess('Done');
            $em->flush();
        }
        return $this->redirect($this->generateUrl('hms_invoice_confirm', array('id' => $entity->getHmsInvoice()->getId())));
    }

    public function groupReportPrintAction(Request $request ,InvoicePathologicalGroup $entity)
    {
        /** @var  $reportArr */
        $reportArr = array();

        /** @var InvoicePathologicalReport $row */
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getHospitalConfig();
        if($config->getId() != $entity->getHmsInvoice()->getHospitalConfig()->getId()){
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        }
        $barcodePrint = $entity->getHmsInvoice()->getInvoice();
        $barcodeInvoice = $this->getBarcode($barcodePrint);
        $barcodePrint = '';
        $barcodeReport = $this->getBarcode($barcodePrint);
        $categories = array();
        foreach ($entity->getInvoiceParticulars() as $item):
            if (!empty($item->getInvoicePathologicalReports())){
                if($item->getParticular()->getCategory()){
                    $categories[] = $item->getParticular()->getCategory();
                    foreach ($item->getInvoicePathologicalReports() as $row):
                        if($row->getPathologicalReport()){
                            $reportArr[$item->getParticular()->getCategory()->getId()][] = $row;
                        }
                    endforeach;
                }

            }
        endforeach;

        $template = "InvoiceParticular:pathologicalGroupReportPrint";

        $hospital = $global->getHospitalConfig();
        if($hospital->isCustomPrint() == 1){
            $template = "Print/{$global->getId()}:pathologicalGroupReportPrint";
        }else{
            $template = "InvoiceParticular:pathologicalGroupReportPrint";
        }
        return $this->render("HospitalBundle:{$template}.html.twig", array(
             'reportGroup'            => $entity,
            'global'            => $global,
            'printUser'         => $this->getUser(),
            'barcodeInvoice'    => $barcodeInvoice,
            'barcodeReport'     => $barcodeReport,
            'categories'            => $categories,
            'reports'            => $reportArr,
        ));
    }


}


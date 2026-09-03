<?php

namespace App\Hospital\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceParticular;
use App\Hospital\Entity\Particular;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice controller.
 *
 */
class PatientController extends AbstractController
{

    public function patientAction(Invoice $entity)
    {

        $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig()->getId();
        if ($inventory == $entity->getHospitalConfig()->getId()) {
            return $this->render('Hospital/AdmissionPatient/index.html.twig', [
                'entity' => $entity,
                'option' => $this->getUser()->getGlobalOption(),
            ]);
        }else{
            return $this->redirect($this->generateUrl('hms_invoice_admission'));
        }
    }

    public function invoiceSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig();
            $item = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function invoiceAdmissionSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig();
            $item = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->searchAdmissionAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function invoiceParticularSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig();
            $item = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->searchParticularAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function invoiceDoctorSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig();
            $item = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->searchDoctorReferredAutoComplete($item,$inventory,[5]);
        }
        return new JsonResponse($item);
    }
    
    public function invoiceReferredSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig();
            $item = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->searchDoctorReferredAutoComplete($item,$inventory,[5,6]);
        }
        return new JsonResponse($item);
    }

    public function patientGlobalDetailsAction($invoice)
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig()->getId();
        $entity = $this->getDoctrine()->getRepository(Invoice::class)->findOneBy(['hospitalConfig' => $config,'invoice' => trim($invoice)]);
        if ($entity) {
            $view =  $this->renderView('Hospital/Patient/ajax-show.html.twig', [
                'entity' => $entity,
            ]);
            return new Response($view);
        } else {
            return new Response("No Record Found!");
        }
    }

    public function patientInfoAction($invoice)
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig()->getId();
        $entity = $this->getDoctrine()->getRepository(Invoice::class)->findOneBy(['hospitalConfig' => $config,'invoice' => trim($invoice)]);
        if ($entity) {
            $customer = $entity->getCustomer();
            $data = [
                'id' => $customer->getId(),
                'patientId' => $customer->getCustomerId(),
                'name' => $customer->getName(),
                'mobile' => $customer->getMobile(),
                'age' => $customer->getAge(),
                'ageType' => $customer->getAgeType(),
                'gender' => $customer->getGender(),
                'height' => $customer->getHeight(),
                'weight' => $customer->getWeight(),
                'bloodPressure' => $customer->getBloodPressure(),
                'address' => $customer->getAddress()
            ];
        }
        return new Response(json_encode($data));

    }

    public function assignDoctorSelectAction()
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getParticularIdName($config,[5]);
        $items = [];
            $items[] = ['value' => '','text'=> '-Change Doctor-'];
        foreach ($entities as $entity):
            $items[] = ['value' => $entity['id'],'text'=> $entity['particularCode'].'-'.$entity['name']];
        endforeach;
        $items[]=['value' => '0','text'=> 'Empty Doctor'];
        return new JsonResponse($items);

    }

    public function referredDoctorSelectAction()
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getParticularIdName($config,[5,6]);
        $items = [];
        $items[] = ['value' => '','text'=> '-Change Referred-'];
        foreach ($entities as $entity):
            $items[] = ['value' => $entity['id'],'text'=> $entity['particularCode'].'-'.$entity['name']];
        endforeach;
        $items[]=['value' => '0','text'=> 'Empty Referred'];
        return new JsonResponse($items);

    }

    public function marketingExecutiveSelectAction()
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getMarketingExecutiveName($config,[14]);
        $items = [];
        $items[] = ['value' => '','text'=> '-Change Marketing Executive-'];
        foreach ($entities as $entity):
            $items[] = ['value' => $entity['id'],'text'=> $entity['particularCode'].'-'.$entity['name']];
        endforeach;
        $items[]=['value' => '0','text'=> 'Empty Marketing Executive'];
        return new JsonResponse($items);

    }

    public function cabinSelectAction(Invoice $invoice)
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $cabin = ($invoice->getCabin()) ? $invoice->getCabin()->getId() : 0;
        $cabins = $this->getDoctrine()->getRepository(Invoice::class)->getExistCabin($config,$cabin);
        $entities = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getCurrentCabins($config,2,$cabins);
        $items = [];
        $items[] = ['value' => '','text'=> '-Change Patient Cabin-'];
        foreach ($entities as $entity):
            $items[] = ['value' => $entity['id'],'text'=> $entity['name']];
        endforeach;
        $items[]=['value' => '0','text'=> 'Empty Cabin'];
        return new JsonResponse($items);

    }

    public function inlinePatientUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(Invoice::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }
        if($data['name']){
            $filedName= "set{$data['name']}";
            $setValue = $em->getRepository(Particular::class)->find($data['value']);
            if($setValue){
                $entity->$filedName($setValue);
            }else {
                $entity->$filedName(NULL);
            }
        }
        $em->persist($entity);
        $em->flush();
        exit;

    }

    public function inlinePatientVisitDoctorUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(Invoice::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }
        if($data['name']){
            $setValue = $em->getRepository(Particular::class)->find($data['value']);
            if($setValue){
                $entity->setAssignDoctor($setValue);
                $entity->setSubTotal($setValue->getPrice());
                $entity->setTotal($setValue->getPrice());
                $entity->setPayment($setValue->getPrice());
            }else {
                $entity->setAssignDoctor(NULL);
                $entity->setSubTotal(0);
                $entity->setTotal(0);
                $entity->setPayment(0);
            }
        }
        $em->persist($entity);
        $em->flush();
        return new Response('success');

    }

    public function inlinePatientFinalBillUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(InvoiceParticular::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }
        $setValue = $data['value'];
        $entity->setDiscountPrice($setValue);
        $em->persist($entity);
        $em->flush();
        return new Response('success');

    }



}


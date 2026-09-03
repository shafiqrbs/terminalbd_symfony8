<?php

namespace App\HumanResource\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Hospital\Entity\HmsVendor;
use App\HumanResource\Entity\LeaveSetup;
use Symfony\Component\HttpFoundation\Request;

/**
 * LeaveSetupController
 *
 */
class LeaveSetupController extends AbstractController
{

    /**
     * Lists all Vendor entities.
     *
     */
    public function leaveSetupAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new HmsVendor();
        $option = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\LeavePolicy::class)->findBy(['status' => 1],['id'=>'ASC']);
        $leaveSetup = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\LeaveSetup::class)->findBy(['globalOption' => $option]);

        /** @var  $leaveSetups */

        $leaveSetups = [];

        if (!empty($leaveSetup)){
            foreach ($leaveSetup as $row):
                $leaveSetups[$row->getLeavePolicy()->getId()] = $row;
            endforeach;
        }

        return $this->render('HumanResource/LeaveSetup/index.html.twig', [
            'entities' => $entities,
            'leaveSetup' => $leaveSetups,
            'entity' => $entity,
        ]);
    }

    public function updateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption();
        $data = $request->request->all();
        $i = 0;
        if ($data and isset($data['leaveSetup']) and !empty($data['leaveSetup'])) {
            foreach ($data['leaveSetup'] as $row) {
                $entity = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\LeaveSetup::class)->findOneBy(['globalOption' => $option, 'leavePolicy' => $row]);
                if (empty($entity)) {
                    $entity = New LeaveSetup();
                }
                $policy = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\LeavePolicy::class)->find($row);
                $entity->setGlobalOption($option);
                $entity->setLeavePolicy($policy);
                $entity->setName($policy->getName());
                $entity->setOffDay($data['offDay'][$i]);
                $em->persist($entity);
                $i++;
            }
            $em->flush();
        }
        return $this->redirect($this->generateUrl('leave_setup'));


    }


}

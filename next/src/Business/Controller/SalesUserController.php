<?php

namespace App\Business\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\DomainUser\Entity\DomainUser;
use Symfony\Component\HttpFoundation\Request;


/**
 * WareHouse controller.
 *
 */
class SalesUserController extends AbstractController
{

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('DomainUser/SalesUser/index.html.twig', [
            'globalOption'  => $globalOption,
            'employees'     => $employees
        ]);
    }

    /**
     * Creates a new WareHouse entity.
     *
     */
    public function createAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $request->request->all();


        foreach ($data['user'] as $key => $value){
            $user = $this->getDoctrine()->getRepository(\App\User\Entity\User::class)->find($value);
            $domainUser = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\DomainUser::class)->findOneBy(['user' => $user->getId()]);
            if(!empty($domainUser)){
                $month = $data['monthlySales'][$key];
                $year = $data['yearlySales'][$key];
                $this->getDoctrine()->getRepository(\App\DomainUser\Entity\DomainUser::class)->updateSalesTarget($user,$month,$year);
            }else{
                $entity = new DomainUser();
                $entity->setGlobalOption($globalOption);
                $entity->setUser($user);
                $entity->setMonthlySales($data['monthlySales'][$key]);
                $entity->setYearlySales($data['yearlySales'][$key]);
                $em->persist($entity);
                $em->flush();
            }

        }
        return $this->redirect($request->headers->get('referer'));

    }



}

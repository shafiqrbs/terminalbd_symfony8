<?php

namespace App\HumanResource\Controller;



use App\HumanResource\Entity\EmployeePayroll;
use App\HumanResource\Entity\EmployeePayrollParticular;
use App\HumanResource\Form\EditUserType;
use App\HumanResource\Form\EmployeePayrollType;
use App\HumanResource\Form\UserType;
use App\User\Entity\User;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use App\Tool\Entity\Designation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


/**
 * Customer controller.
 *
 */
class EmployeePayrollController extends Controller
{


    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('HumanResource/EmployeePayroll/index.html.twig', array(
            'globalOption'  => $globalOption,
            'employees'     => $employees,
        ));
    }

    public function newAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new User();
        $form = $this->createCreateForm($entity);
        $particulars = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\PayrollSetting::class)->findBy(array('globalOption'=>$entity->getGlobalOption()));
        return $this->render('HumanResource/EmployeePayroll/new.html.twig', array(
            'entity'  => $entity,
            'particulars' => $particulars,
            'form'   => $form->createView(),
        ));
    }

    public function createAction(Request $request)
    {

        $entity = new User();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $globalOption = $this->getUser()->getGlobalOption();
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setGlobalOption($globalOption);
            $entity->setPassword($globalOption);
            $entity->getProfile()->upload();
            $entity->setPassword('@123456');
            $entity->setUserGroup('employee');
            $entity->setDomainOwner(2);
            $entity->getProfile()->setEmail($entity->getEmail());
            $entity->getEmployeePayroll()->setEmployeeName($entity->getProfile()->getName());
            $entity->getEmployeePayroll()->setGlobalOption($globalOption);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('employee_payroll_edit',array('user' => $entity->getId())));
        }
        return $this->render('HumanResource/EmployeePayroll/new.html.twig', array(
            'entity' => $entity,
            'particulars' => '',
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Vendor entity.
     *
     * @param EmployeePayroll $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(User $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        $user = $this->getDoctrine()->getRepository(\App\User\Entity\User::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into UserType::configureOptions() and pass them as form options: $user,$option,$location), $entity, array(
        $form = $this->createForm(new UserType($user,$option,$location), $entity, array(
            'action' => $this->generateUrl('employee_payroll_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    public function editAction(User $user)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->editCreateForm($user);
        $particulars = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\PayrollSetting::class)->findBy(array('globalOption'=>$user->getGlobalOption()));
        return $this->render('HumanResource/EmployeePayroll/edit.html.twig', array(
            'user'  => $user,
            'entity'  => $user->getEmployeePayroll(),
            'particulars' => $particulars,
            'form'   => $form->createView(),
        ));
    }





    /**
     * Creates a form to create a Vendor entity.
     *
     * @param EmployeePayroll $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function editCreateForm(User $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        $user = $this->getDoctrine()->getRepository(\App\User\Entity\User::class);
        $designationRepository = $this->getDoctrine()->getRepository(Designation::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into EditUserType::configureOptions() and pass them as form options: $user,$option,$location,$designationRepository), $entity, array(
        $form = $this->createForm(new EditUserType($user,$option,$location,$designationRepository), $entity, array(
            'action' => $this->generateUrl('employee_payroll_update',array('id'=> $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }




    public function updateAction(Request $request ,User $entity)
    {
        /* @var $entity User */

        $form = $this->editCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->getProfile()->upload();
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('employee_payroll_edit',array('user' => $entity->getId())));
        }
        $particulars = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\PayrollSetting::class)->findBy(array('globalOption'=>$entity->getGlobalOption()));
        return $this->render('HumanResource/EmployeePayroll/edit.html.twig', array(
            'user'  => $entity,
            'entity' => $entity->getEmployeePayroll(),
            'particulars' => $particulars,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a form to create a Vendor entity.
     *
     * @param EmployeePayroll $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function settingCreateForm(EmployeePayroll $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        $user = $this->getDoctrine()->getRepository(\App\User\Entity\User::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into EmployeePayrollType::configureOptions() and pass them as form options: $user,$option,$location), $entity, array(
        $form = $this->createForm(new EmployeePayrollType($user,$option,$location), $entity, array(
            'action' => $this->generateUrl('employee_payroll_setting_update',array('id'=> $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    public function payrollSettingAction(User $user)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->settingCreateForm($user->getEmployeePayroll());
        $particulars = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\PayrollSetting::class)->findBy(array('globalOption'=>$user->getGlobalOption()));
        return $this->render('HumanResource/EmployeePayroll/payroll.html.twig', array(
            'user'  => $user,
            'entity'  => $user->getEmployeePayroll(),
            'particulars' => $particulars,
            'form'   => $form->createView(),
        ));
    }



    public function payrollSettingUpdateAction(Request $request ,EmployeePayroll $entity)
    {

        $form = $this->settingCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            $this->getDoctrine()->getRepository(\App\HumanResource\Entity\EmployeePayroll::class)->insertUpdateParticular($entity,$data);
            $this->getDoctrine()->getRepository(\App\HumanResource\Entity\EmployeePayroll::class)->insertUpdate($entity);
            return $this->redirect($this->generateUrl('employee_payroll_setting',array('user' => $entity->getEmployee()->getId())));
        }
        $particulars = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\PayrollSetting::class)->findBy(array('globalOption'=>$entity->getGlobalOption()));
        return $this->render('HumanResource/EmployeePayroll/payroll.html.twig', array(
            'user'  => $entity->getEmployee(),
            'entity' => $entity,
            'particulars' => $particulars,
            'form'   => $form->createView(),
        ));
    }

    public function approveAction(User $user)
    {
        $entity = $user->getEmployeePayroll();
        $em = $this->getDoctrine()->getManager();
        $approve = $this->getUser();
        $entity->setApprovedBy($approve);
        $em->persist($entity);
        $em->flush();
        return new Response('success');




    }

    public function particularDeleteAction(EmployeePayrollParticular $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            return new Response('success');

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->get('session')->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return new Response('failed');


    }



}

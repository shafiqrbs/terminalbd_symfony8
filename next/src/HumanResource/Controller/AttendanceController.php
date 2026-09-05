<?php

namespace App\HumanResource\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DomainUser\Entity\HrAttendance;
use App\DomainUser\Entity\HrAttendanceMonth;
use App\HumanResource\Entity\DailyAttendance;
use App\HumanResource\Entity\EmployeeLeave;
use App\HumanResource\Form\EmployeeLeaveType;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\DomainUser\Entity\Customer;
use App\DomainUser\Form\CustomerType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;

/**
 * Customer controller.
 *
 */
class AttendanceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }



    public function indexAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('HumanResource/Attendance/index.html.twig', array(
            'globalOption'  => $globalOption,
            'employees'     => $employees,
        ));
    }


    /**
     * Displays a form to create a new Customer entity.
     *
     */
    public function attendanceAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        $blackoutdate ='';
        $calendarBlackout = $em->getRepository(\App\HumanResource\Entity\Weekend::class)->findOneBy(array('globalOption' => $globalOption));
        $blackOutDate =  $calendarBlackout ->getWeekendDate();
        if($blackOutDate){
            $blackoutdate = (array_map('trim',array_filter(explode(',',$blackOutDate))));
        }
        return $this->render('HumanResource/DailyAttendance/attendance.html.twig', array(
            'globalOption'  => $globalOption,
            'employees'     => $employees,
            'blackoutdate'  => $blackoutdate,
        ));

    }

    public function employeeDetailsAction(User $user)
    {
        $em = $this->em;
        $entity = new EmployeeLeave();
        $form = $this->createCreateForm($entity,$user->getId());
        $globalOption = $this->getUser()->getGlobalOption();
        $monthWiseAttendance = $em->getRepository(\App\HumanResource\Entity\Attendance::class)->findBy(array('employee'=>$user),array('year'=>'DESC','month'=>'ASC'));
        $leaveTypeWiseAbsence = $em->getRepository(\App\HumanResource\Entity\EmployeeLeave::class)->leaveTypeWiseAbsence($user);
        $entities = $em->getRepository(\App\HumanResource\Entity\EmployeeLeave::class)->findBy(array('employee'=>$user),array('updated'=>'DESC'));
        return $this->render('HumanResource/Attendance/employeeDetails.html.twig', array(
            'user'  => $user,
            'entities'  => $entities,
            'monthWiseAttendance'     => $monthWiseAttendance,
            'leaveTypeWiseAbsence'     => $leaveTypeWiseAbsence,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Vendor entity.
     *
     * @param Vendor $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(EmployeeLeave $entity,$user)
    {
        $option = $this->getUser()->getGlobalOption();
        $form = $this->createForm(EmployeeLeaveType::class, $entity, array('action' => $this->generateUrl('attendance_user_create',array('user'=> $user)),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $option));
        return $form;
    }

    public function employeeLeaveCreateAction(Request $request ,User $user)
    {
        $em = $this->em;
        $entity = new EmployeeLeave();
        $global = $this->getUser()->getGlobalOption();
        $form = $this->createCreateForm($entity,$user->getId());
        $form->handleRequest($request);
        $startDate = $entity->getStartDate();
        $endDate = $entity->getEndDate();
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        $offDay = $interval->format('%a');
        $offDay = (int)$offDay +1;

        $leaveDay = $em->getRepository(\App\HumanResource\Entity\EmployeeLeave::class)->leaveTypeWiseAbsence($user,$entity->getLeaveSetup()->getId());
        if(!empty($leaveDay)){
            $remaining = $leaveDay['offDay'] + $offDay;
        }else{
            $remaining = $offDay;
        }

        $datetime = new \DateTime("now");
        if ($form->isSubmitted() && $form->isValid() and $entity->getLeaveSetup()->getOffDay() >= $remaining) {
            $em = $this->em;
            $entity->setGlobalOption($global);
            $entity->setEmployee($user);
            $entity->setYear($datetime->format('Y'));
            $entity->setStartDate($start);
            $entity->setEndDate($end);
            $entity->setNoOffDay($offDay);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('attendance_user', array('user' => $user->getId())));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',$entity->getLeaveSetup()->getName() ." is not available"
        );
        $monthWiseAttendance = $em->getRepository(\App\HumanResource\Entity\Attendance::class)->findBy(array('employee'=>$user),array('year'=>'DESC','month'=>'ASC'));
        $leaveTypeWiseAbsence = $em->getRepository(\App\HumanResource\Entity\EmployeeLeave::class)->leaveTypeWiseAbsence($user);
        $entities = $em->getRepository(\App\HumanResource\Entity\EmployeeLeave::class)->findBy(array(),array('updated'=>'DESC'));
        return $this->render('HumanResource/Attendance/employeeDetails.html.twig', array(
            'user'  => $user,
            'entities'     => $entities,
            'monthWiseAttendance'     => $monthWiseAttendance,
            'leaveTypeWiseAbsence'     => $leaveTypeWiseAbsence,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function employeeLeaveApproveAction(EmployeeLeave $leave)
    {
        $em = $this->em;
        $leave->setApprovedBy($this->getUser());
        $em->persist($leave);
        $em->flush();
        $blackoutdate ='';
        $calendarBlackout = $em->getRepository(\App\HumanResource\Entity\Weekend::class)->findOneBy(array('globalOption' => $leave->getGlobalOption()));
        if($calendarBlackout){
            $blackOutDate =  $calendarBlackout ->getWeekendDate();
            if($blackOutDate){
                $blackoutdate = (array_map('trim',array_filter(explode(',',$blackOutDate))));
            }
        }
        $em->getRepository(\App\HumanResource\Entity\Attendance::class)->leaveAttendance($leave,$blackoutdate);
        return new Response('success');
    }

    public function employeeLeaveDeleteAction(EmployeeLeave $leave)
    {
        $em = $this->em;
        $em->remove($leave);
        $em->flush();
        return new Response('success');
    }

    public function manualAttendanceAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('HumanResource/DailyAttendance/manualAttendance.html.twig', array(
            'globalOption'  => $globalOption,
            'employees'     => $employees,
        ));

    }

    public function manualAttendanceCreate(Request $request)
    {

    }



}

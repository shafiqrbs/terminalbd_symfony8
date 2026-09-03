<?php

namespace App\HumanResource\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\HumanResource\Entity\Attendance;
use App\HumanResource\Entity\DailyAttendance;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Customer controller.
 *
 */
class DailyAttendanceController extends AbstractController
{

    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            50  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all Customer entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\HumanResource\Entity\DailyAttendance::class)->attendanceYearMonth($globalOption,$data);
        return $this->render('HumanResource/DailyAttendance/index.html.twig', [
            'entities' => $entities,
            'searchForm' => $data,
        ]);
    }


    /**
     * Displays a form to create a new Customer entity.
     *
     */
    public function attendanceAction()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $blackoutdate ='';

        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        $calendarBlackout = $em->getRepository(\App\HumanResource\Entity\Weekend::class)->findOneBy(['globalOption' => $globalOption]);
        $blackOutDate =  $calendarBlackout ->getWeekendDate();
        if($blackOutDate){
            $blackoutdate = (array_map(trim(...),array_filter(explode(',',$blackOutDate))));
        }
        $this->getDoctrine()->getRepository(\App\HumanResource\Entity\Attendance::class)->setupMonthlyAttendance($globalOption,$employees,$blackoutdate);
        $currentMonthEmployeeAttendance = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\Attendance::class)->currentMonthEmployeeAttendance($globalOption);

        return $this->render('HumanResource/DailyAttendance/attendance.html.twig', [
            'globalOption'                          => $globalOption,
            'employees'                             => $employees,
            'currentMonthEmployeeAttendance'        => $currentMonthEmployeeAttendance,
            'blackoutdate'                          => $blackoutdate,
        ]);

    }


    public function addAttendanceAction(Attendance $attendance)
    {
        $present = $_REQUEST['present'];
        $em = $this->getDoctrine()->getManager();
        $datetime = new DateTime("now");
        $today  = $datetime->format('d');
        $month  = $datetime->format('F');
        $year   = $datetime->format('Y');
        $exist = $this->getDoctrine()->getRepository(\App\HumanResource\Entity\DailyAttendance::class)->checkLeaveToday($attendance);
        if($exist){
            $entity = $exist;
        }else{
            $entity = New DailyAttendance();
        }
        $entity->setAttendance($attendance);
        $entity->setUser($attendance->getEmployee());
        $entity->setGlobalOption($attendance->getGlobalOption());
        if($present > 0 ){
            $entity->setPresent(true);
            $entity->setPresentDay($present);
            $entity->setPresentIn(true);
            $entity->setPresentOut(true);
        }else{
            $entity->setPresent(false);
            $entity->setPresentDay(null);
            $entity->setPresentIn(true);
            $entity->setPresentOut(true);
        }
        $entity->setMonth($month);
        $entity->setYear($year);
        $em->persist($entity);
        $em->flush();
        $this->getDoctrine()->getRepository(\App\HumanResource\Entity\Attendance::class)->employeeTotalPresentDay($attendance);
        exit;

    }

    public function showAction($month,$year)
    {
        $showMonth = new DateTime("$month $year");
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $blackoutdate ='';
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployeeEntities($globalOption);
        $calendarBlackout = $em->getRepository(\App\HumanResource\Entity\Weekend::class)->findOneBy(['globalOption' => $globalOption]);
        $blackOutDate =  $calendarBlackout ->getWeekendDate();
        if($blackOutDate){
            $blackoutdate = (array_map(trim(...),array_filter(explode(',',$blackOutDate))));
        }
        return $this->render('HumanResource/DailyAttendance/show.html.twig', [
            'showMonth' => $showMonth,
            'globalOption'  => $globalOption,
            'employees'     => $employees,
            'blackoutdate'  => $blackoutdate,
        ]);
    }

    public function employeeList()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('HumanResource/DailyAttendance/employee.html.twig', [
            'globalOption'  => $globalOption,
            'employees'     => $employees,
        ]);
    }

    public function employeeAttendance(User $user)
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('HumanResource/DailyAttendance/employee.html.twig', [
            'globalOption'  => $globalOption,
            'employees'     => $employees,
        ]);
    }



    public function manualAttendanceAction()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('HumanResource/DailyAttendance/manualAttendance.html.twig', [
            'globalOption'  => $globalOption,
            'employees'     => $employees,
        ]);

    }

    public function manualAttendanceCreate(Request $request)
    {

    }


}

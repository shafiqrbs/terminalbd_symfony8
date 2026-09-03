<?php

namespace App\Accounting\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Accounting\Entity\Payroll;
use App\Accounting\Form\SalarySettingType;

/**
 * SalarySetting controller.
 *
 */
class SalarySettingController extends AbstractController
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
            25  /*limit per page*/
        );
        return $pagination;
    }



    /**
     * Lists all SalarySetting entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\SalarySetting::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/SalarySetting/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data
        ));
    }
    /**
     * Creates a new SalarySetting entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Payroll();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $globalOption = $this->getUser()->getGlobalOption();
            $entity->setGlobalOption($globalOption);
            $totalAmount = ($entity->getBasicAmount() + $entity->getBonusAmount() + $entity->getOtherAmount() + $entity->getAdvanceAmount());
            $entity->setTotalAmount($totalAmount);
            $date = $entity->getCreated();
            //$effected =  date(strtotime($date),'m-Y');
            //$salaryInfo = $entity->getName().' Effected date: '.$effected.' Total amount: '.$entity->getTotalAmount();
            //$entity->setSalaryInfo($salaryInfo);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('account_salarysetting'));
        }

        return $this->render('Accounting/SalarySetting/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a SalarySetting entity.
     *
     * @param Payroll $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Payroll $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SalarySettingType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new SalarySettingType($globalOption), $entity, array(
            'action' => $this->generateUrl('account_salarysetting_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
       return $form;
    }

    /**
     * Displays a form to create a new SalarySetting entity.
     *
     */
    public function newAction()
    {
        $entity = new Payroll();
        $form   = $this->createCreateForm($entity);

        return $this->render('Accounting/SalarySetting/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a SalarySetting entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\SalarySetting::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalarySetting entity.');
        }

       return $this->render('Accounting/SalarySetting/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing SalarySetting entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\SalarySetting::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalarySetting entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Accounting/SalarySetting/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a SalarySetting entity.
    *
    * @param Payroll $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Payroll $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SalarySettingType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new SalarySettingType($globalOption), $entity, array(
            'action' => $this->generateUrl('account_salarysetting_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing SalarySetting entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\SalarySetting::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalarySetting entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            $totalAmount = ($entity->getBasicAmount() + $entity->getBonusAmount() + $entity->getOtherAmount());
            $entity->setTotalAmount($totalAmount);

            $date = $entity->getUpdated();
            $effected =  $date->format('d-m-Y');
            $salaryInfo = $entity->getName().' Effected date: '.$effected.' Total amount: '.$entity->getTotalAmount();
            $entity->setSalaryInfo($salaryInfo);
            $em->flush();

            return $this->redirect($this->generateUrl('account_salarysetting_edit', array('id' => $id)));
        }

        return $this->render('Accounting/SalarySetting/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a SalarySetting entity.
     *
     */
    public function deleteAction(Payroll $entity)
    {

        $em = $this->em;
        if (!$entity) {
              $em->remove($entity);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('account_salarysetting'));
    }

    /**
     * Displays a form to edit an existing Expenditure entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\SalarySetting::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        $field ='set'.$data['name'];
        $entity->$field($data['value']);
        $em->flush();
        exit;
    }

    public function approveAction(Payroll $entity)
    {
        if (!empty($entity)) {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            return new \Symfony\Component\HttpFoundation\Response('success');
        } else {
            return new \Symfony\Component\HttpFoundation\Response('failed');
        }
        exit;
    }

    public function statusAction(Payroll $entity)
    {
        if (!empty($entity)) {
            $em = $this->em;
            $status = $entity->getStatus();
            if($status == 1){
                $entity->setStatus(0);
            } else{
                $entity->setStatus(1);
            }
            $em->flush();

            return new \Symfony\Component\HttpFoundation\Response('success');
        } else {
            return new \Symfony\Component\HttpFoundation\Response('failed');
        }
        exit;
    }

    public function salaryAmountAction(Request $request)
    {
        $data = $request->request->all();
        $id = $data['id'];
        $entity = $this->em->getRepository(\App\Accounting\Entity\SalarySetting::class)->find($id);
        $totalAmount = $entity->getTotalAmount();
        return new \Symfony\Component\HttpFoundation\Response($totalAmount);
        exit;

    }
}

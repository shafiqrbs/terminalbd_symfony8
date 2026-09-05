<?php


namespace App\HumanResource\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\HumanResource\Entity\HrDepartment;
use App\HumanResource\Form\DepartmentType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Particular controller.
 *
 */
class DepartmentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Particular entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $entity = new HrDepartment();
        $form = $this->createCreateForm($entity);
        $config = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->findBy(['globalOption' => $config],['name'=>'ASC']);
        return $this->render('HumanResource/Department/index.html.twig', [
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }
    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new HrDepartment();
        $config = $this->getUser()->getGlobalOption();
       $entities = $this->em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->findBy(['globalOption' => $config],['name'=>'ASC']);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption();
            $entity->setGlobalOption($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('hrdepartment'));
        }

        return $this->render('HumanResource/Department/index.html.twig', [
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param HrDepartment $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(HrDepartment $entity)
    {
        $form = $this->createForm(DepartmentType::class, $entity, [
            'action' => $this->generateUrl('hrdepartment_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();
       $entities = $this->em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->findBy(['globalOption' => $config],['name'=>'ASC']);
        $entity = $em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('HumanResource/Department/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param HrDepartment $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(HrDepartment $entity)
    {
        $form = $this->createForm(DepartmentType::class, $entity, [
            'action' => $this->generateUrl('hrdepartment_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();
       $entities = $this->em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->findBy(['globalOption' => $config],['name'=>'ASC']);

        $entity = $em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('hrdepartment'));
        }

        return $this->render('HumanResource/Setting/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }

        return $this->redirect($this->generateUrl('hrdepartment'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\HumanResource\Entity\HrDepartment::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('hrdepartment'));
    }


}

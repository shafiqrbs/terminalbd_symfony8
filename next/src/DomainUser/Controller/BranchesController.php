<?php

namespace App\DomainUser\Controller;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\DomainUser\Entity\Branches;
use App\DomainUser\Form\BranchesType;

/**
 * Branches controller.
 *
 */
class BranchesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }



    /**
     * Lists all Branches entities.
     */

    public function indexAction()
    {

        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\DomainUser\Entity\Branches::class)->findBy(array('globalOption' => $globalOption),array('name'=>'asc'));
        return $this->render('DomainUser/Branches/index.html.twig', array(
            'entities' => $entities,
        ));
    }

    /**
     * Lists all Branches entities.
     *
     */

    public function branchesEmployeeAction(Branches $branches)
    {
        $em = $this->em;
        return $this->render('DomainUser/Branches/employee.html.twig', array(
            'branches' => $branches,
        ));
    }


    /**
     * Creates a new Branches entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Branches();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $globalOption = $this->getUser()->getGlobalOption();
            $entity->setGlobalOption($globalOption);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('domain_branches'));
        }

        return $this->render('DomainUser/Branches/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Branches entity.
     *
     * @param Branches $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Branches $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(BranchesType::class, $entity, array('action' => $this->generateUrl('domain_branches_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption, 'location' => $location));
        return $form;
    }

    /**
     * Displays a form to create a new Branches entity.
     *
     */

    public function newAction()
    {
        $entity = new Branches();
        $form   = $this->createCreateForm($entity);

        return $this->render('DomainUser/Branches/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Branches entity.
     *
     */

    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Branches::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Branches entity.');
        }
        return $this->render('DomainUser/Branches/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Branches entity.
     *
     */

    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Branches::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Branches entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('DomainUser/Branches/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Branches entity.
    *
    * @param Branches $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Branches $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(BranchesType::class, $entity, array('action' => $this->generateUrl('domain_branches_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption, 'location' => $location));
         return $form;
    }
    /**
     * Edits an existing Branches entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Branches::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Branches entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('domain_branches_edit', array('id' => $id)));
        }

        return $this->render('DomainUser/Branches/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Branches entity.
     *
     */

    public function deleteAction(Branches $entity)
    {
        $em = $this->em;
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('domain_branches'));

    }


    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $globalOption = $this->getUser()->getGlobalOption();
            $item = $this->em->getRepository(\App\DomainUser\Entity\Branches::class)->searchAutoComplete($item,$globalOption);
        }
        return new JsonResponse($item);
    }

    public function searchBranchNameAction($branch)
    {
        return new JsonResponse(array(
            'id'=>$branch,
            'text'=>$branch
        ));
    }


}

<?php

namespace App\Accounting\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\ExpenseCategory;
use App\Accounting\Form\ExpenseCategoryType;
use App\Hospital\Entity\HmsCategory;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * ExpenseCategory controller.
 *
 */
class ExpenseCategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    /**
     * Lists all ExpenseCategory entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->findBy(array('globalOption' => $option),array( 'parent'=>'asc' , 'name' =>'asc' ));
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/ExpenseCategory/index.html.twig', array(
            'entities' => $pagination,
        ));

    }

    public function paginate($entities)
    {
        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            50  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Creates a new ExpenseCategory entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ExpenseCategory();
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createCreateForm($entity,$globalOption);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity->setGlobalOption($globalOption);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('expensecategory_new', array('id' => $entity->getId())));
        }

        return $this->render('Accounting/ExpenseCategory/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a ExpenseCategory entity.
     *
     * @param ExpenseCategory $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ExpenseCategory $entity, $globalOption)
    {

        $em = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class);
        $emHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class);
        $form = $this->createForm(ExpenseCategoryType::class, $entity, array('action' => $this->generateUrl('expensecategory_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'em' => $em, 'accountHead' => $emHead, 'globalOption' => $globalOption));
        return $form;
    }

    /**
     * Displays a form to create a new ExpenseCategory entity.
     *
     */
    public function newAction()
    {
        $entity = new ExpenseCategory();
        $globalOption = $this->getUser()->getGlobalOption();
        $form   = $this->createCreateForm($entity,$globalOption);

        return $this->render('Accounting/ExpenseCategory/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a ExpenseCategory entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ExpenseCategory entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Accounting/ExpenseCategory/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ExpenseCategory entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ExpenseCategory entity.');
        }
        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);

        return $this->render('Accounting/ExpenseCategory/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a ExpenseCategory entity.
     *
     * @param ExpenseCategory $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(ExpenseCategory $entity,$globalOption)
    {
        $em = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class);
        $emHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class);
        $form = $this->createForm(ExpenseCategoryType::class, $entity, array('action' => $this->generateUrl('expensecategory_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'em' => $em, 'accountHead' => $emHead, 'globalOption' => $globalOption));


        return $form;
    }
    /**
     * Edits an existing ExpenseCategory entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ExpenseCategory entity.');
        }

        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('expensecategory'));
        }

        return $this->render('Accounting/ExpenseCategory/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a ExpenseCategory entity.
     *
     */
    public function deleteAction(HmsCategory $entity)
    {
        $em = $this->em;
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('expensecategory'));
    }

    /**
     * Creates a form to delete a ExpenseCategory entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('expensecategory_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
            ;
    }

    /**
     * Lists all ExpenseCategory entities.
     *
     */
    public function sortingAction()
    {
        $em = $this->em;
        $entities = $em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->findBy(array('level'=> 1 ),array('sorting'=>'asc'));
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/ExpenseCategory/sorting.html.twig', array(
            'entities' => $pagination,
        ));

    }

    public function sortedAction(Request $request){

        $data = $request->request->get('menuItem');
        $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->setFeatureOrdering($data);
        exit;

    }

    public function featureAction()
    {
        $data = $_REQUEST;
        $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->setExpenseCategoryFeature($data);
        return $this->redirect($this->generateUrl('expensecategory_sorting'));

    }
}

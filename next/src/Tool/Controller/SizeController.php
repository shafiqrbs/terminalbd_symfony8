<?php

namespace App\Tool\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Inventory\Entity\ItemSize;
use App\Tool\Form\SizeType;

/**
 * ItemSize controller.
 *
 */
class SizeController extends AbstractController
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
            20  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all ItemSize entities.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function indexAction()
    {

        $em = $this->em;
        $entities = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->findBy(['isValid'=>1],['code'=>'asc']);
        $entities = $this->paginate($entities);
        return $this->render('Tool/Size/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ItemSize entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ItemSize();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setStatus(false);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('size'));
        }

        return $this->render('Tool/Size/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ItemSize entity.
     *
     * @param ItemSize $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ItemSize $entity)
    {
        $form = $this->createForm(SizeType::class, $entity, [
            'action' => $this->generateUrl('size_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ItemSize entity.
     */
    #[IsGranted('ROLE_ADMIN')]

    public function newAction()
    {
        $entity = new ItemSize();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/Size/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ItemSize entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('InventoryBundle:Size')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemSize entity.');
        }

        return $this->render('Tool/Size/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing ItemSize entity.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemSize entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Tool/Size/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ItemSize entity.
     *
     * @param ItemSize $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ItemSize $entity)
    {
         $form = $this->createForm(SizeType::class, $entity, [
            'action' => $this->generateUrl('size_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing ItemSize entity.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemSize entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('size_edit', ['id' => $id]));
        }

        return $this->render('Tool/Size/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Deletes a ItemSize entity.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAction(ItemSize $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
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
        }
        return $this->redirect($this->generateUrl('size'));
    }

    public function autoInsertAction()
    {

        $a = 0;
        $em = $this->em;
        for( $i = 0; $i < 200; $i++ ) {
            $a += 5;
            $entity = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->findOneBy(['isValid'=>1,'name'=>$a]);
            if(empty($entity)) {
                $entity = new ItemSize();
                $entity->setName($a);
                $entity->setStatus(true);
                $entity->setIsValid(true);
                $em->persist($entity);
            }
            $em->flush();
        }



    }


}

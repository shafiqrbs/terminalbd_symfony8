<?php

namespace App\Hospital\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\AccessoriesType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;


/**
 * AccessoriesController controller.
 *
 */
class AccessoriesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {

        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }

    /**
     * Lists all Particular entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getAccessoriesParticular($hospital);
        $pagination = $this->paginate($entities);
        $entity = new Particular();
        $form = $this->createCreateForm($entity);
        return $this->render('Hospital/Accessories/index.html.twig', [
            'pagination' => $pagination,
            'entity' => $entity,
            'formShow'            => 'hide',
            'form'   => $form->createView(),
        ]);

    }

    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getAccessoriesParticular($config);
        $pagination = $this->paginate($entities);
        $entity = new Particular();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setHospitalConfig($config);
            $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(8);
            $entity->setService($service);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('hms_accessories'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Required field does not input"
        );
        return $this->render('Hospital/Accessories/index.html.twig', [
            'entity' => $entity,
            'pagination' => $pagination,
            'formShow'            => 'show',
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Particular $entity)
    {

        $form = $this->createForm(AccessoriesType::class, $entity, [
            'action' => $this->generateUrl('hms_accessories_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
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
        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getAccessoriesParticular($config);
        $pagination = $this->paginate($entities);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Hospital/Accessories/index.html.twig', [
            'pagination'        => $pagination,
            'entity'            => $entity,
            'formShow'            => 'show',
            'form'              => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Particular $entity)
    {
        $form = $this->createForm(AccessoriesType::class, $entity, [
            'action' => $this->generateUrl('hms_accessories_update', ['id' => $entity->getId()]),
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
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getAccessoriesParticular($config);
        $pagination = $this->paginate($entities);
        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('hms_accessories'));
        }
        return $this->render('Hospital/Accessories/index.html.twig', [
            'pagination'      => $pagination,
            'entity'      => $entity,
            'formShow'            => 'show',
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction(Particular $entity)
    {
        $em = $this->em;
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
        return $this->redirect($this->generateUrl('hms_accessories'));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Particular $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('hms_accessories'));
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Hospital\Entity\Particular::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        $setField = 'set'.$data['name'];
        $entity->$setField(abs($data['value']));
        $em->flush();
        exit;

    }
}

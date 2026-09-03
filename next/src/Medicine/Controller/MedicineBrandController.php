<?php

namespace App\Medicine\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Medicine\Entity\MedicineBrand;
use App\Medicine\Form\MedicineBrandType;
use Symfony\Component\HttpFoundation\Response;

/**
 * MedicineBrand controller.
 *
 */
class MedicineBrandController extends AbstractController
{

    /**
     * Lists all MedicineBrand entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findAll();

        return $this->render('Medicine/MedicineBrand/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new MedicineBrand entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MedicineBrand();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('medicine_show', ['id' => $entity->getId()]));
        }

        return $this->render('Medicine/MedicineBrand/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MedicineBrand entity.
     *
     * @param MedicineBrand $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MedicineBrand $entity)
    {
        $form = $this->createForm(new MedicineBrandType(), $entity, [
            'action' => $this->generateUrl('medicine_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new MedicineBrand entity.
     *
     */
    public function newAction()
    {
        $entity = new MedicineBrand();
        $form   = $this->createCreateForm($entity);
        return $this->render('Medicine/MedicineBrand/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a MedicineBrand entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineBrand entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Medicine/MedicineBrand/new.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing MedicineBrand entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineBrand entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Medicine/MedicineBrand/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a MedicineBrand entity.
     *
     * @param MedicineBrand $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MedicineBrand $entity)
    {
        $form = $this->createForm(new MedicineBrandType(), $entity, [
            'action' => $this->generateUrl('medicine_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', 'submit', ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing MedicineBrand entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineBrand entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('medicine_edit', ['id' => $id]));
        }

        return $this->render('Medicine/MedicineBrand/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a MedicineBrand entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MedicineBrand entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('medicine'));
    }

    /**
     * Creates a form to delete a MedicineBrand entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {

    }

    public function medicineDetailsAction(){

        $data = $_REQUEST['medicine'];
        $entity = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($data);
        $items = [];
        if($entity) {
            $items =[
                'generic' => $entity->getMedicineGeneric()->getName(),
                'company' => $entity->getMedicineCompany()->getName(),
                'medicineForm' => $entity->getMedicineForm()
            ];
        }
        return new Response(json_encode($items));


    }


    public function autoSearchMedicineAction(Request $request)
    {
        $item = $_REQUEST['term'];
        $items = [];

        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);

    }

    public function selectSearchMedicineAction(Request $request)
    {
        $item = trim($_REQUEST['q']);
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineAutoComplete($item);
        }
        return new JsonResponse($entities);

    }

    public function autoSearchGenericAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }


    public function searchProductNameAction($medicine)
    {
        return new JsonResponse([
            'id' => $medicine,
            'text' => $medicine
        ]);
    }

    public function searchBrandNameAutoCompleteAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchBrandNameAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

    public function searchCompanyNameAutoCompleteAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchCompanyNameAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

    public function searchGenericNameAutoCompleteAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchGenericNameAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

    public function searchMedicineGenericNameAutoCompleteAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineGenericAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
	         //   $items[] = array('value' => $entity['id'], 'label' => $entity['text'], 'desc' => $entity['text'], 'icon' => 'img/avatar.png');

            endforeach;
        }
        return new JsonResponse($items);
    }

    public function searchPackSizeAutoCompleteAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchPackSizeAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

    public function searchStrengthAutoCompleteAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchStrengthAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

   public function searchMedicineFormAutoCompleteAction(Request $request)
    {
        $item = $_REQUEST['term'];
        $items = [];
        if ($item) {
            $entities = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineFormAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

}

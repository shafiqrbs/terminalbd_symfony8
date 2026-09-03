<?php

namespace App\Medicine\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Medicine\Form\MedicineEditType;
use App\Medicine\Form\MedicineType;
use App\Medicine\Entity\MedicineBrand;
use Symfony\Component\HttpFoundation\Request;

/**
 * MedicineBrand controller.
 *
 */
class MedicineController extends AbstractController
{

    /**
     * Lists all MedicineBrand entities.
     *
     */
    public function indexAction()
    {

        $em = $this->getDoctrine()->getManager();
        $option = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findBy(['globalOption' => $option]);
        $entity = new MedicineBrand();
        $form   = $this->createCreateForm($entity);
        return $this->render('Medicine/MedicineBrand/medicine.html.twig', [
            'pagination' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);

    }

    /**
     * Creates a new MedicineBrand entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $data = $request->request->all();
        $option = $this->getUser()->getGlobalOption();
        $entity = new MedicineBrand();
        $exist = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findOneBy(['name'=>$entity->getName(),'strength'=> $entity->getStrength(),'medicineForm'=>$entity->getMedicineForm()]);
        if(empty($exist)) {
            $em = $this->getDoctrine()->getManager();
            $entity->setGlobalOption($option);
            $entity->setStrength($data['strength']);
            $entity->setMedicineForm($data['medicineForm']);
            $entity->setPackSize($data['packSize']);
            $entity->setName($data['brand']);
            $entity->setPrice($data['mrp']);
            $generic = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineGeneric::class)->checkGenericName($data['generic']);
            $entity->setMedicineGeneric($generic);
            $company = $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineCompany::class)->checkCompanyName($data['companyName']);
            $entity->setMedicineCompany($company);
            $em->persist($entity);
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Medicine\Entity\MedicineStock::class)->insertGlobalToLocalStock($option,$entity);
            return $this->redirect($this->generateUrl('medicine_user'));
        }
        $this->get('session')->getFlashBag()->add(
            'error',"Data has been already exist"
        );
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findBy(['globalOption' => $option]);
        $form   = $this->createCreateForm($entity);
        return $this->render('Medicine/MedicineBrand/medicine.html.twig', [
            'pagination' => $entities,
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
        $form = $this->createForm(new MedicineType(), $entity, [
            'action' => $this->generateUrl('medicine_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
                'id' => 'medicine',
            ]
        ]);
        return $form;
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
        return $this->render('Medicine/MedicineBrand/edit.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
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
        $form = $this->createForm(new MedicineEditType(), $entity, [
            'action' => $this->generateUrl('medicine_update', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
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

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('medicine'));
        }

        return $this->render('Medicine/MedicineBrand/edit.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    public function copyAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineBrand entity.');
        }
        $medicine = new MedicineBrand();
        $medicine->setGlobalOption($entity->getGlobalOption());
        $medicine->setName($entity->getName());
        $medicine->setMedicineCompany($entity->getMedicineCompany());
        $medicine->setMedicineGeneric($entity->getMedicineGeneric());
        $medicine->setMedicineForm($entity->getMedicineForm());
        $em->persist($medicine);
        $em->flush();
        return $this->redirect($this->generateUrl('medicine_edit', ['id' => $medicine->getId()]));
    }

}

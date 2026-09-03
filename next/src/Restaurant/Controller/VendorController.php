<?php

namespace App\Restaurant\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Accounting\Entity\AccountVendor;
use App\Restaurant\Entity\Vendor;
use App\Restaurant\Form\VendorType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vendor controller.
 *
 */
class VendorController extends AbstractController
{



    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new AccountVendor();
        $form = $this->createCreateForm($entity);
        $config = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findWithSearch($globalOption,$data);
        return $this->render('Restaurant/Vendor/index.html.twig', [
            'entities' => $entities->getResult(),
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }
    /**
     * Creates a new Vendor entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AccountVendor();
        $config = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(['globalOption' => $config],['companyName'=>'ASC']);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setGlobalOption($config);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_vendor', ['id' => $entity->getId()]));
        }

        return $this->render('Restaurant/Vendor/index.html.twig', [
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Vendor entity.
     *
     * @param Vendor $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(AccountVendor $entity)
    {
        $form = $this->createForm(new VendorType(), $entity, [
            'action' => $this->generateUrl('restaurant_vendor_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to edit an existing Vendor entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(['globalOption' => $config],['companyName'=>'ASC']);

        $entity = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $editForm = $this->createEditForm($entity);


        return $this->render('Restaurant/Vendor/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Vendor entity.
     *
     * @param Vendor $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(AccountVendor $entity)
    {
        $form = $this->createForm(new VendorType(), $entity, [
            'action' => $this->generateUrl('restaurant_vendor_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Vendor entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(['globalOption' => $config],['companyName'=>'ASC']);

        $entity = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_vendor_edit', ['id' => $id]));
        }

        return $this->render('Restaurant/Vendor/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->get('session')->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->get('session')->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }

        return $this->redirect($this->generateUrl('restaurant_vendor'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($id);

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
        $this->get('session')->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('restaurant_vendor'));
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getRestaurantConfig();
            $item = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountVendor::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchVendorNameAction($vendor)
    {
        return new JsonResponse([
            'id'=>$vendor,
            'text'=>$vendor
        ]);
    }


}

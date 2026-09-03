<?php

namespace App\Hospital\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Medicine\Entity\MedicineVendor;
use App\Hospital\Form\VendorType;
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

    #[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]
    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findWithSearch($this->getUser(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Hospital/Vendor/index.html.twig', [
            'entities' => $pagination,
            'searchForm'   => $data,
        ]);
    }

    #[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]
    public function newAction()
    {
        $em = $this->em;
        $entity = new MedicineVendor();
        $form = $this->createCreateForm($entity);
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        return $this->render('Hospital/Vendor/new.html.twig', [
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
        $entity = new MedicineVendor();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findWithSearch($this->getUser());
        $pagination = $this->paginate($entities);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        $company = $data['vendor']['companyName'];
        $exitVendor = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findOneBy(['medicineConfig'=>$config,'companyName'=>$company]);
        if ($form->isValid() and empty($exitVendor)) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $entity->setMedicineConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('hms_vendor'));
        }
        $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->insertVendor($entity);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"User mobile no already exist,Please try again."
        );
        return $this->render('Hospital/Vendor/index.html.twig', [
            'entities' => $pagination,
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
    private function createCreateForm(MedicineVendor $entity)
    {
        $form = $this->createForm(new VendorType(), $entity, [
            'action' => $this->generateUrl('hms_vendor_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    #[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findOneBy(['medicineConfig'=> $config,'id'=>$id]);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Hospital/Vendor/new.html.twig', [
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
    private function createEditForm(MedicineVendor $entity)
    {
        $form = $this->createForm(new VendorType(), $entity, [
            'action' => $this->generateUrl('hms_vendor_update', ['id' => $entity->getId()]),
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
        $em = $this->em;
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findWithSearch($this->getUser());
        $pagination = $this->paginate($entities);

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $data = $request->request->all();
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->updateVendorCompanyName($entity);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('hms_vendor'));
        }

        return $this->render('Hospital/Vendor/index.html.twig', [
            'entities'      => $pagination,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    #[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
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

        return $this->redirect($this->generateUrl('hms_vendor'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->find($id);

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
        return $this->redirect($this->generateUrl('hms_vendor'));
    }

    public function autoSearchAutoCompleteAction(Request $request)
    {
        $item = $_REQUEST['term'];
        $items = [];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->searchAutoComplete($inventory,$item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->searchVendor($inventory,$item);
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

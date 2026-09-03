<?php

namespace App\Tool\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Medicine\Entity\MedicineBrand;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use App\Tool\Form\MedicineEditType;
use App\Tool\Form\MedicineType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * ItemColor controller.
 *
 */
class MedicineController extends AbstractController
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

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findWithSearch($data);
        $pagination = $this->paginate($entities);
        return $this->render('Tool/MedicineBrand/index.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $data,
        ]);
    }

    /**
     * Lists all MedicineBrand entities.
     *
     */
    public function newAction()
    {

        $em = $this->em;
        $entity = new MedicineBrand();
        $form   = $this->createCreateForm($entity);
        return $this->render('Tool/MedicineBrand/medicine.html.twig', [
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
        $em = $this->em;
        $data = $request->request->all();
        $file = $request->files->all()['medicine']['file'];
        $option = $this->getUser()->getGlobalOption();
        $entity = new MedicineBrand();
        $entity->setGlobalOption($option);
        $entity->setStrength($data['strength']);
        $entity->setMedicineForm($data['medicineForm']);
        $entity->setPackSize($data['packSize']);
        $entity->setName($data['brand']);
        $exist = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findOneBy(['name'=>$entity->getName(),'strength'=> $entity->getStrength(),'medicineForm'=>$entity->getMedicineForm()]);
        if(empty($exist)){
            $generic = $this->em->getRepository(\App\Medicine\Entity\MedicineGeneric::class)->checkGenericName($data['generic']);
            $entity->setMedicineGeneric($generic);
            $company = $this->em->getRepository(\App\Medicine\Entity\MedicineCompany::class)->checkCompanyName($data['companyName']);
            $entity->setMedicineCompany($company);
            $fileName = $file->getClientOriginalName();
            $imgName = uniqid() . '.' . $fileName;
            $path = $entity->getUploadDir() . $imgName;
            if (!file_exists($entity->getUploadDir())) {
                mkdir($entity->getUploadDir(), 0777, true);
            }
            // TODO(migration): AbstractController::get() was removed; inject this service.
            $this->get('helper.imageresizer')->resizeImage(480, $path, $file);
            $entity->setPath($imgName);
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('medicinebrand'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been already exist"
        );
        $form   = $this->createCreateForm($entity);
        return $this->render('Tool/MedicineBrand/medicine.html.twig', [
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
            'action' => $this->generateUrl('medicinebrand_create', ['id' => $entity->getId()]),
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
        $em = $this->em;

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineBrand entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Tool/MedicineBrand/edit.html.twig', [
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
            'action' => $this->generateUrl('medicinebrand_update', ['id' => $entity->getId()]),
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
        $em = $this->em;

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineBrand entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $exist = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findOneBy(['name'=>$entity->getName(),'medicineForm' => $entity->getMedicineForm(),'strength'=>$entity->getStrength()]);
        if ($editForm->isValid() and empty($exist)) {
            if($entity->upload()){
                $entity->removeUpload();
                $entity->upload();
            }else{
                $entity->upload();
            }
            $em->flush();
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        }
        return $this->render('Tool/MedicineBrand/edit.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    public function copyAction($id)
    {
        $em = $this->em;
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
        return $this->redirect($this->generateUrl('medicinebrand_edit', ['id' => $medicine->getId()]));
    }

    #[IsGranted('ROLE_medicinebrand_STOCK')]

    public function deleteAction(Request $request , MedicineBrand $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineStock entity.');
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
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, MedicineBrand $entity)
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
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }



    public function medicineDetailsAction(){

        $data = $_REQUEST['medicine'];
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($data);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineAutoComplete($item);
        }
        return new JsonResponse($entities);

    }

    public function autoSearchGenericAction(Request $request)
    {
        $item = trim($_REQUEST['term']);
        $items = [];
        if ($item) {
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchBrandNameAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchCompanyNameAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchGenericNameAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineGenericAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchPackSizeAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchStrengthAutoComplete($item);
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
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->searchMedicineFormAutoComplete($item);
            foreach ($entities as $entity):
                $items[] = ['id' => $entity['id'], 'value' => $entity['text']];
            endforeach;
        }
        return new JsonResponse($items);
    }

    public function selectMedicineFormAction()
    {

        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->selectMedicineFormAutoComplete();
        $items = [];
        $items[]=['value' => '','text'=> '-MedicineForm-'];
        foreach ($entities as $entity):
            $items[]=['value' => $entity['id'],'text'=> $entity['id']];
        endforeach;
        return new JsonResponse($items);

    }

    public function selectStrengthAction()
    {
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->selectStrengthAutoComplete();
        $items = [];
        $items[]=['value' => '','text'=> '-Strength-'];
        foreach ($entities as $entity):
            $items[]=['value' => $entity['id'],'text'=> $entity['id']];
        endforeach;
        return new JsonResponse($items);

    }

    public function inlineItemUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }

        if($data['name'] == 'MedicineForm'){

            $exist = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findOneBy(['name'=>$entity->getName(),'strength'=> $entity->getStrength() , 'medicineForm'=>$data['value']]);
            if(empty($exist)){
                $entity->setMedicineForm($data['value']);
            }

        }elseif($data['name'] == 'Strength'){

            $exist = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->findOneBy(['name'=>$entity->getName(),'strength'=> $data['value'] , 'medicineForm'=> $entity->getMedicineForm()]);
            if(empty($exist)){
                $entity->setStrength($data['value']);
            }

        }else{

            $setValue = "set{$data['name']}";
            $entity->$setValue($data['value']);

        }
        $em->persist($entity);
        $em->flush();
        return new Response('success');

    }


}

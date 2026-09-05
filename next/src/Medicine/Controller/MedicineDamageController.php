<?php

namespace App\Medicine\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Medicine\Entity\MedicineDamage;
use App\Medicine\Form\MedicineDamageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class MedicineDamageController extends AbstractController
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
     * Lists all Damage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineDamage::class)->findWithSearch($config,$data);
	    $pagination = $this->paginate($entities);
	    return $this->render('Medicine/Damage/index.html.twig', [
            'entities' => $pagination
        ]);
    }

    public function newAction(Request $request){

        $entity = new MedicineDamage();
        $form = $this->createCreateForm($entity);
        return $this->render('Medicine/Damage/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a new Damage entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MedicineDamage();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        /* @var $stock MedicineStock */
        $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($data['damage']['medicineStock']);
        if(empty($stock)){
            $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($data['medicineStock']);
        }
        if ($form->isSubmitted() && $form->isValid() and !empty($stock) and $stock->getRemainingQuantity() >= $entity->getQuantity()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $entity->setMedicineConfig($config);
            $entity->setMedicineStock($stock);
            $entity->setPurchasePrice($stock->getPurchasePrice());
            $entity->setSubTotal($stock->getPurchasePrice() * $entity->getQuantity());
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'damage');
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('medicine_damage', ['id' => $entity->getId()]));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"Damage quantity must be less or equal current stock quantity"
        );
        return $this->render('Medicine/Damage/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Damage entity.
     *
     * @param Damage $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MedicineDamage $entity)
    {
        $form = $this->createForm(MedicineDamageType::class, $entity, [
            'action' => $this->generateUrl('medicine_damage_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to edit an existing Damage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineDamage::class)->findBy(['medicineConfig' => $config],['companyName'=>'ASC']);

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineDamage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Medicine/Damage/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Damage entity.
     *
     * @param Damage $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MedicineDamage $entity)
    {
        $form = $this->createForm(MedicineDamageType::class, $entity, [
            'action' => $this->generateUrl('medicine_damage_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Damage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineDamage::class)->findBy(['medicineConfig' => $config],['companyName'=>'ASC']);

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineDamage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('medicine_damage'));
        }
        return $this->render('Medicine/Damage/index.html.twig', [
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Damage entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineDamage::class)->find($id);
        $stock = $entity->getMedicineStock();
        $em->remove($entity);
        $em->flush();
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'damage');
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('medicine_damage'));
    }

	public function approvedAction($id)
	{
		$em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getMedicineConfig();
		$damage = $em->getRepository(\App\Medicine\Entity\MedicineDamage::class)->findOneBy(['medicineConfig' => $config , 'id' => $id]);
		if (!empty($damage) and ($damage->getProcess() == 'Created')) {
			$em = $this->em;
			$damage->setProcess('Approved');
			$em->flush();
			$this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertGlobalDamageTransaction($this->getUser()->getGlobalOption(),$damage);
			return new Response('success');
		} else {
			return new Response('failed');
		}
	}


}

<?php

namespace App\Medicine\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Medicine\Entity\MedicineStockAdjustment;
use App\Medicine\Form\MedicineStockAdjustmentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class StockAdjustmentController extends AbstractController
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
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStockAdjustment::class)->findWithSearch($config,$data);
	    $pagination = $this->paginate($entities);
	    return $this->render('Medicine/StockAdjustment/index.html.twig', [
            'entities' => $pagination
        ]);
    }

    public function newAction(Request $request){

        $entity = new MedicineStockAdjustment();
        $form = $this->createCreateForm($entity);
        return $this->render('Medicine/StockAdjustment/new.html.twig', [
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
        $entity = new MedicineStockAdjustment();
        $data = $request->request->all();
        /* @var $stock MedicineStock */
        $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($data['adjustment']['medicineStock']);
        if(empty($stock)){
            $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($data['medicineStock']);
        }
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $entity->setMedicineConfig($config);
            $entity->setMedicineStock($stock);
            if($entity->getMode() == "Minus"){
                if($entity->getQuantity() > 0){
                    $entity->setQuantity("-{$entity->getQuantity()}");
                }
                if($entity->getBonus() > 0){
                    $entity->setBonus("-{$entity->getBonus()}");
                }
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('stock_adjustment', ['id' => $entity->getId()]));
        }
        return $this->render('Medicine/StockAdjustment/new.html.twig', [
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
    private function createCreateForm(MedicineStockAdjustment $entity)
    {
        $form = $this->createForm(MedicineStockAdjustmentType::class, $entity, [
            'action' => $this->generateUrl('stock_adjustment_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }


    /**
     * Deletes a Damage entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineStockAdjustment::class)->find($id);
        $stock = $entity->getMedicineStock();
        $em->remove($entity);
        $em->flush();
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'damage');
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('stock_adjustment'));
    }

	public function approvedAction($id)
	{
		$em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $damage = $this->em->getRepository(\App\Medicine\Entity\MedicineStockAdjustment::class)->findOneBy(['medicineConfig'=>$config,'id'=>$id]);
		if (!empty($damage) and ($damage->getProcess() == 'Created')) {
			$em = $this->em;
			$damage->setProcess('Approved');
			$em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->stockAdjustment($damage->getMedicineStock());
            return new Response('success');
		} else {
			return new Response('failed');
		}
	}

    public function stockSummaryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineStockAdjustment::class)->stockAdjustmentReport($config, $data);
        return $this->render('Medicine/StockAdjustment/adjustment.html.twig', [
            'entities' => $entities,
            'searchForm' => $data,
        ]);
    }


}

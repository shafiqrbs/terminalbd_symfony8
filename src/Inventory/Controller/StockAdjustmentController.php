<?php

namespace App\Inventory\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Inventory\Entity\ItemStockAdjustment;
use App\Inventory\Form\StockAdjustmentType;
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
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();
        $entities = $this->em->getRepository(\App\Inventory\Entity\ItemStockAdjustment::class)->findWithSearch($config,$data);
	    $pagination = $this->paginate($entities);
	    return $this->render('Inventory/StockAdjustment/index.html.twig', [
            'entities' => $pagination
        ]);
    }

    public function newAction(Request $request){

        $entity = new ItemStockAdjustment();
        $form = $this->createCreateForm($entity);
        return $this->render('Inventory/StockAdjustment/new.html.twig', [
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
        $entity = new ItemStockAdjustment();
        $data = $request->request->all();
        /* @var $stock Item */
        $stock = $this->em->getRepository(\App\Inventory\Entity\Item::class)->find($data['adjustment']['item']);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setConfig($config);
            $entity->setItem($stock);
            if($entity->getMode() == "Minus"){
                $entity->setQuantity("-{$entity->getQuantity()}");
                $entity->setBonus("-{$entity->getBonus()}");
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('inv_stock_adjustment', ['id' => $entity->getId()]));
        }
        return $this->render('Inventory/StockAdjustment/new.html.twig', [
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
    private function createCreateForm(ItemStockAdjustment $entity)
    {
        $form = $this->createForm(StockAdjustmentType::class, $entity, [
            'action' => $this->generateUrl('inv_stock_adjustment_create'),
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
        $entity = $em->getRepository(\App\Inventory\Entity\ItemStockAdjustment::class)->find($id);
        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('inv_stock_adjustment'));
    }

	public function approvedAction($id)
	{
		$em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $damage = $this->em->getRepository(\App\Inventory\Entity\ItemStockAdjustment::class)->findOneBy(['config'=>$config,'id'=>$id]);
        /* @var $damage ItemStockAdjustment */

        if (!empty($damage) and ($damage->getProcess() == 'Created')) {
			$em = $this->em;
			$damage->setProcess('Approved');
			$em->flush();
            $this->em->getRepository(\App\Inventory\Entity\Item::class)->stockAdjustment($damage->getItem());
            return new Response('success');
		} else {
			return new Response('failed');
		}
	}


}

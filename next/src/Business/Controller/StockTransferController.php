<?php

namespace App\Business\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\ItemStockAdjustment;
use App\Business\Entity\ProductTransfer;
use App\Business\Entity\WearHouse;
use App\Business\Form\StockAdjustmentType;
use App\Business\Form\StockTransferType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class StockTransferController extends AbstractController
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
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        $entities = $this->em->getRepository(\App\Business\Entity\ProductTransfer::class)->findWithSearch($config,$data);
	    $pagination = $this->paginate($entities);
        $form = $this->createCreateForm(new ProductTransfer());
        $wearhouses = $this->em->getRepository(WearHouse::class)->findBy(array('businessConfig' => $config),array('name'=>'ASC'));
        return $this->render('Business/StockTransfer/index.html.twig', array(
            'entities' => $pagination,
            'wearhouses' => $wearhouses,
            'form' => $form->createView()
        ));
    }


    /**
     * Creates a new Damage entity.
     *
     */
    public function createAction(Request $request)
    {

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $data = $request->request->all();
        $particular = $data['transfer']['businessParticular'];
        $entity = new ProductTransfer();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity->setBusinessConfig($config);
            if($particular){
                $item = $this->em->getRepository(BusinessParticular::class)->findOneBy(array('businessConfig'=>$config,'name'=>$particular));
                $entity->setBusinessParticular($item);
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('business_stock_transfer'));
        }
        $entities = $this->em->getRepository(\App\Business\Entity\ProductTransfer::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Business/StockAdjustment/new.html.twig', array(
            'entities' => $pagination,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Damage entity.
     *
     * @param ProductTransfer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ProductTransfer $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $form = $this->createForm(StockTransferType::class, $entity, array('action' => $this->generateUrl('business_transfer_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'config' => $config));
        return $form;
    }


	public function deleteAction(ProductTransfer $entity)
	{
		$em = $this->em;
        $em->remove($entity);
        $em->flush();
		return new Response('success');

	}

    public function approvedAction(ProductTransfer $entity)
    {

        if (!empty($entity) and ($entity->getProcess() == 'Created')) {
            $em = $this->em;
            $entity->setProcess('Approved');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->stockTransfer($entity);
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }

    public function reverseAction(ProductTransfer $entity)
    {
        if (!empty($entity) and ($entity->getProcess() == 'Approved')) {
            $em = $this->em;
            $entity->setProcess('Created');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $em->createQuery("DELETE BusinessBundle:BusinessStockHistory e WHERE e.itemTransfer = '{$entity->getId()}'")->execute();
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }


}

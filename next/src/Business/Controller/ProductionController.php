<?php
namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\BusinessProduction;
use App\Business\Form\PreproductionType;
use App\Business\Form\ProductionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * ProductionController controller.
 *
 */
class ProductionController extends AbstractController
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
	 * ;
	 */
	#[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
	public function indexAction()
	{

		$em = $this->em;
		$data = $_REQUEST;
	//	$data['type']= 7;
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
		$entities = $this->em->getRepository(\App\Business\Entity\BusinessProduction::class)->findWithSearch($config,$data);
		$pagination = $this->paginate($entities);
		$type = $this->em->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(['status'=>1]);
		$category = $this->em->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig'=>$config,'status'=>1]);
		return $this->render('Business/Production/index.html.twig', [
			'pagination' => $pagination,
			'types' => $type,
			'categories' => $category,
			'config' => $config,
			'searchForm' => $data,
		]);

	}
	/**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return Form The form
     */
    private function createProductionCostingForm(BusinessParticular $entity)
    {

        $form = $this->createForm(ProductionType::class, $entity, [
            'action' => $this->generateUrl('business_production_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
                'id' => 'production',
            ]
        ]);
        return $form;
    }



    public function postProductionAction(BusinessParticular  $entity)
    {


    	$em = $this->em;
        $config =$this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        if($entity->getBusinessParticularType()->getSlug() != 'post-production' and $entity->getBusinessConfig()->getId() != $config){
            return $this->redirect($this->generateUrl('business_stock'));
        }
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $editForm = $this->createProductionCostingForm($entity);
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($config,$type = ['consumable','stock']);
        return $this->render('Business/Production/production.html.twig', [
            'entity'      => $entity,
            'particulars' => $particulars,
            'form'   => $editForm->createView(),
        ]);
    }

    public function productionUpdateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createProductionCostingForm($entity);
        $editForm->handleRequest($request);
        $data = $request->request->all();
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->setSalesPrice($data['productionSalesPrice']);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_stock'));
        }

        return $this->render('Business/Production/production.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }


	public function preProductionAction(BusinessParticular  $item){

		$entity = new BusinessProduction();
    	$em = $this->em;
		$config =$this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
		if($item->getBusinessParticularType()->getSlug() != 'pre-production' and $item->getBusinessConfig()->getId() != $config){
			return $this->redirect($this->generateUrl('business_stock'));
		}
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
		$editForm = $this->createPreProductionForm($entity,$item);
		$particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($config,$type = ['consumable','stock']);
		return $this->render('Business/Production/pre-production.html.twig', [
			'entity'      => $item,
			'particulars' => $particulars,
			'form'   => $editForm->createView(),
		]);
	}

	/**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return Form The form
     */
    private function createPreProductionForm(BusinessProduction $entity, BusinessParticular $item)
	{

		$form = $this->createForm(PreproductionType::class, $entity, [
			'action' => $this->generateUrl('business_pre_production_create', ['item' => $item->getId()]),
			'method' => 'POST',
			'attr' => [
				'class' => 'form-horizontal',
				'novalidate' => 'novalidate',
				'id' => 'production',
			]
		]);
		return $form;
	}

	private function updatePreProductionForm(BusinessParticular $entity)
	{

		$form = $this->createForm(PreproductionType::class, $entity, [
			'action' => $this->generateUrl('business_pre_production_update', ['id' => $entity->getId()]),
			'method' => 'POST',
			'attr' => [
				'class' => 'form-horizontal',
				'novalidate' => 'novalidate',
				'id' => 'production',
			]
		]);
		return $form;
	}

	public function preProductionCreateAction(BusinessParticular $item , Request $request)
    {
        $em = $this->em;
	    $entity = new BusinessProduction();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createPreProductionForm($entity,$item);
        $editForm->handleRequest($request);
        $data = $request->request->all();
        $productionElementPrice = $this->em->getRepository(\App\Business\Entity\BusinessProductionElement::class)->getProductPurchaseSalesPrice($item);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
	        $entity->setBusinessParticular($item);
	        $entity->setPurchasePrice($productionElementPrice['purchasePrice']);
	        $entity->setSalesPrice($productionElementPrice['salesPrice']);
	        $entity->setQuantity($data['productionQuantity']);
	        $entity->setPurchaseSubTotal($productionElementPrice['purchasePrice'] * $data['productionQuantity']);
	        $entity->setSalesSubTotal($productionElementPrice['salesPrice'] * $data['productionQuantity']);
	        $em->persist($entity);
	        $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_production'));
        }

        return $this->render('Business/Production/production.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

	public function preProductionUpdateAction(Request $request)
	{
		$em = $this->em;
		$entity = new BusinessProduction();
		if (!$entity) {
			throw $this->createNotFoundException('Unable to find Particular entity.');
		}
		$editForm = $this->updatePreProductionForm($entity);
		$editForm->handleRequest($request);
		$data = $request->request->all();
		if ($editForm->isSubmitted() && $editForm->isValid()) {
			$particular = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->find($data['particularId']);
            $productionElementPrice = $this->em->getRepository(\App\Business\Entity\BusinessProductionElement::class)->getProductPurchaseSalesPrice($particular);
			$entity->setBusinessParticular($particular);
			$entity->setPurchasePrice($data['purchasePrice']);
            $entity->setPurchasePrice($productionElementPrice['purchasePrice']);
            $entity->setSalesPrice($productionElementPrice['salesPrice']);
            $entity->setQuantity($data['quantity']);
            $entity->setPurchaseSubTotal($productionElementPrice['purchasePrice'] * $data['quantity']);
            $entity->setSalesSubTotal($productionElementPrice['salesPrice'] * $data['quantity']);
			$em->persist($entity);
			$em->flush();
			$this->requestStack->getSession()->getFlashBag()->add(
				'success',"Data has been updated successfully"
			);
			return $this->redirect($this->generateUrl('business_production'));
		}

		return $this->render('Business/Production/production.html.twig', [
			'entity'      => $entity,
			'form'   => $editForm->createView(),
		]);
	}


    public function particularSearchAction(BusinessParticular $particular)
    {
        return new Response(json_encode(['particularId'=> $particular->getId() ,'price'=> $particular->getSalesPrice() , 'purchasePrice'=> $particular->getPurchasePrice(), 'quantity'=> 1 , 'minimumPrice'=> '']));
    }

    public function addParticularAction(Request $request, BusinessParticular $particular)
    {
        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $data = ['particularId' => $particularId , 'quantity' => $quantity,'price' => $price ];
        $this->em->getRepository(\App\Business\Entity\BusinessProductionElement::class)->insertProductionElement($particular, $data);
	    $particularReturn = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateSalesPrice($particular);
        $production = $this->em->getRepository(\App\Business\Entity\BusinessProductionElement::class)->particularProductionElements($particular);
        return new Response(json_encode(['subTotal' => $particularReturn->getSalesPrice(),'purchasePrice' => $particular->getPurchasePrice(),'particulars' => $production]));

    }

    public function deleteAction(BusinessParticular $particular , $id){

        $em = $this->em;
        $element = $this->em->getRepository(\App\Business\Entity\BusinessProductionElement::class)->find($id);
        if (!$element) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($element);
        $em->flush();
        $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateSalesPrice($particular);
        exit;
    }

    public function showAction($id)
    {
	    $em = $this->em;
	    $entity = $em->getRepository(\App\Business\Entity\BusinessProduction::class)->find($id);
	    return $this->render('Business/Production/show.html.twig', [
		    'entity'      => $entity,
	    ]);
    }

	public function approvedAction($id)
	{
		$em = $this->em;
		$production = $em->getRepository(\App\Business\Entity\BusinessProduction::class)->find($id);
		if (!empty($production) and ($production->getProcess() == 'created')) {
			$production->setProcess('approved');
			$em->flush();
			$this->em->getRepository(\App\Business\Entity\BusinessProduction::class)->insertStockProductionItem($production);
			return new Response('success');
		} else {
			return new Response('failed');
		}
		exit;
	}

}

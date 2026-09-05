<?php

namespace App\Restaurant\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Restaurant\Entity\Particular;
use App\Restaurant\Entity\ProductionBatch;
use App\Restaurant\Entity\ProductionElement;
use App\Restaurant\Entity\ProductionValueAdded;
use App\Restaurant\Form\ParticularType;
use App\Restaurant\Form\ProductionBatchType;
use App\Restaurant\Form\ProductionType;
use App\Restaurant\Form\ProductType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


/**
 * ParticularController controller.
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
        return $pagination;
    }

    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {

        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $data = $_REQUEST;
        $entities = $this->em->getRepository(\App\Restaurant\Entity\ProductionBatch::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Restaurant/Production/index.html.twig', array(
            'pagination' => $pagination,
            'searchForm' => $data,
        ));

    }

    public function newAction(Request $request){

        $entity = new ProductionBatch();
        $form = $this->createCreateForm($entity);
        return $this->render('Restaurant/Production/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a new Damage entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ProductionBatch();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
            $entity->setRestaurantConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_production_new'));
        }

        return $this->render('Restaurant/Production/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Damage entity.
     *
     * @param ProductionBatch $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ProductionBatch $entity)
    {
        $config = $this->getUser()->getGlobalOption();
        $form = $this->createForm(ProductionBatchType::class, $entity, array('action' => $this->generateUrl('restaurant_production_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $config));
        return $form;
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createProductionCostingForm(Particular $entity)
    {

        $form = $this->createForm(ProductionType::class, $entity, array(
            'action' => $this->generateUrl('restaurant_production_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function productionAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->findOneBy(array('restaurantConfig'=>$config, 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createProductionCostingForm($entity);
        $particulars = $em->getRepository(\App\Restaurant\Entity\Particular::class)->getProductionParticular($config->getId());
        $productionValues = $this->em->getRepository(\App\Restaurant\Entity\ProductionValueAdded::class)->getProductionAdded($entity);
        return $this->render('Restaurant/Production/production.html.twig', array(
            'entity'      => $entity,
            'particulars' => $particulars,
            'productionValues' => $productionValues,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function productionUpdateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $productionPrice = (floatval($entity->getValueAddedAmount()) + floatval($entity->getProductionElementAmount()));
        $entity->setPurchasePrice($productionPrice);
        $em->flush();
        return $this->redirect($this->generateUrl('restaurant_production_edit',array('id' => $id)));
    }


    public function particularSearchAction(Particular $particular)
    {
        return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getPrice() , 'purchasePrice'=> $particular->getPurchasePrice(), 'quantity'=> 1 , 'minimumPrice'=> '', 'instruction'=>'')));
    }

    public function addParticularAction(Request $request, Particular $invoice)
    {
        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price );
        $this->em->getRepository(\App\Restaurant\Entity\ProductionElement::class)->insertProductionElement($invoice, $invoiceItems);
        $subTotal = $this->em->getRepository(\App\Restaurant\Entity\ProductionElement::class)->getProductionPrice($invoice);
        $invoice->setProductionElementAmount($subTotal);
        $em->flush();
        $invoiceParticulars = $this->em->getRepository(\App\Restaurant\Entity\ProductionElement::class)->particularProductionElements($invoice);
        $result = array('subTotal' => $subTotal , 'invoiceParticulars' => $invoiceParticulars);
        return new Response(json_encode($result));

    }

    public function itemValueAddAction(Request $request, ProductionValueAdded $entity)
    {
        $em = $this->em;
        $amount = $_REQUEST['amount'];
        $entity->setAmount($amount);
        $em->flush();
        $productionItem = $entity->getProductionItem();
        $this->em->getRepository(\App\Restaurant\Entity\ProductionElement::class)->particularProductionElements($productionItem);
        $subTotal = $this->em->getRepository(\App\Restaurant\Entity\ProductionValueAdded::class)->totalValues($productionItem);
        $productionItem->setValueAddedAmount($subTotal);
        $em->flush();
        return new Response('success');

    }

    public function productionElementDeleteAction(Particular $product, ProductionElement $particular){

        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $subTotal = $this->em->getRepository(\App\Restaurant\Entity\ProductionElement::class)->getProductionPrice($product);
        $em->flush();
        $invoiceParticulars = $this->em->getRepository(\App\Restaurant\Entity\ProductionElement::class)->particularProductionElements($product);
        $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->updateProductionPrice($product->getRestaurantConfig()->getId());
        $result = array('subTotal' => $subTotal,'invoiceParticulars' => $invoiceParticulars);
        return new Response(json_encode($result));

    }

    public function sortingAction()
    {
        $entity = new Particular();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $pagination = $em->getRepository(\App\Restaurant\Entity\Particular::class)->productSortingList($config,array('product','stockable'));
        $editForm = $this->createCreateForm($entity);
        return $this->render('Restaurant/Product/sorting.html.twig', array(
            'pagination' => $pagination,
            'entity' => $entity,
            'form'   => $editForm->createView(),
        ));

    }

    public function sortedAction(Request $request)
    {
        $data = $request ->request->get('item');
        $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->setProductSorting($data);
        exit;
    }

    public function approveAction(ProductionBatch $batch){

        $em = $this->em;
        if (!$batch) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $batch->setProcess('approved');
        $batch->setApprovedBy($this->getUser());
        $em->flush();
        $this->em->getRepository(\App\Restaurant\Entity\ProductionExpense::class)->productionElementExpense($batch);
        $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->updateRemoveStockQuantity($batch->getProductionItem(),"production-in");
        return new Response("success");

    }

}

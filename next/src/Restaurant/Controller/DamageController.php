<?php

namespace App\Restaurant\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Restaurant\Entity\RestaurantDamage;
use App\Restaurant\Form\RestaurantDamageType;
use App\Restaurant\Form\ResturantDamageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class DamageController extends AbstractController
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
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entities = $this->em->getRepository(\App\Restaurant\Entity\RestaurantDamage::class)->findBy(array('restaurantConfig' => $config),array('created'=>'ASC'));
	    $pagination = $this->paginate($entities);
        return $this->render('Restaurant/Damage/index.html.twig', array(
            'entities' => $pagination,
        ));
    }

    public function newAction(Request $request){

        $entity = new RestaurantDamage();
        $form = $this->createCreateForm($entity);
        return $this->render('Restaurant/Damage/new.html.twig', array(
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
        $entity = new RestaurantDamage();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
            $entity->setRestaurantConfig($config);
            $entity->setSubTotal($entity->getParticular()->getPurchasePrice() * $entity->getQuantity());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_damage', array('id' => $entity->getId())));
        }

        return $this->render('Restaurant/Damage/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Damage entity.
     *
     * @param Damage $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(RestaurantDamage $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
    	$form = $this->createForm(ResturantDamageType::class, $entity, array('action' => $this->generateUrl('restaurant_damage_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'config' => $config));
        return $form;
    }

    /**
     * Displays a form to edit an existing Damage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entities = $this->em->getRepository(\App\Restaurant\Entity\RestaurantDamage::class)->findBy(array('medicineConfig' => $config),array('companyName'=>'ASC'));

        $entity = $em->getRepository(\App\Restaurant\Entity\RestaurantDamage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }

        $editForm = $this->createEditForm($entity);


        return $this->render('Restaurant/Damage/index.html.twig', array(
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Damage entity.
    *
    * @param Damage $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(RestaurantDamage $entity)
    {
	    $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
    	$form = $this->createForm(ResturantDamageType::class, $entity, array('action' => $this->generateUrl('restaurant_damage_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'config' => $config));
        return $form;
    }
    /**
     * Edits an existing Damage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entities = $this->em->getRepository(\App\Restaurant\Entity\RestaurantDamage::class)->findBy(array('medicineConfig' => $config),array('companyName'=>'ASC'));

        $entity = $em->getRepository(\App\Restaurant\Entity\RestaurantDamage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_damage'));
        }
        return $this->render('Restaurant/Damage/index.html.twig', array(
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Damage entity.
     *
     */
    public function deleteAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Restaurant\Entity\RestaurantDamage::class)->find($id);
        $stock = $entity->getBusinessParticular();
        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('restaurant_damage'));
    }

	public function approvedAction($id)
	{
		$em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
		$damage = $em->getRepository(\App\Restaurant\Entity\RestaurantDamage::class)->findOneBy(array('restaurantConfig' => $config , 'id' => $id));
		if (!empty($damage) and ($damage->getProcess() == 'Created')) {
            $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getDamageQnt($damage);
            if($damage->getRestaurantConfig()->isStockHistory() == 1 ) {
                $this->em->getRepository(\App\Restaurant\Entity\RestaurantStockHistory::class)->processInsertDamageItem($damage);
            }
			$em = $this->em;
			$damage->setProcess('Approved');
			$em->flush();
			return new Response('success');
		} else {
			return new Response('failed');
		}

	}

}

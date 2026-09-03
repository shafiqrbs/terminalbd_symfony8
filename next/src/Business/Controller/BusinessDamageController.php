<?php

namespace App\Business\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessDamage;
use App\Business\Form\BusinessDamageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class BusinessDamageController extends AbstractController
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
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessDamage::class)->findBy(array('businessConfig' => $config),array('created'=>'ASC'));
	    $pagination = $this->paginate($entities);
        return $this->render('Business/Damage/index.html.twig', array(
            'entities' => $pagination,
        ));
    }

    public function newAction(Request $request){

        $entity = new BusinessDamage();
        $form = $this->createCreateForm($entity);
        return $this->render('Business/Damage/new.html.twig', array(
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
        $entity = new BusinessDamage();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
            $entity->setBusinessConfig($config);
            $entity->setSubTotal($entity->getBusinessParticular()->getPurchasePrice() * $entity->getQuantity());
            $em->persist($entity);
            $em->flush();
	        $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateRemoveStockQuantity($entity->getBusinessParticular(),'damage');
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('business_damage', array('id' => $entity->getId())));
        }

        return $this->render('Business/Damage/new.html.twig', array(
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
    private function createCreateForm(BusinessDamage $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
     // TODO(migration): SF3+ takes a form type class-string, not an instance.
     // Move these constructor arguments into BusinessDamageType::configureOptions() and pass them as form options: $config), $entity, array(
    	$form = $this->createForm(new BusinessDamageType($config), $entity, array(
            'action' => $this->generateUrl('business_damage_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to edit an existing Damage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessDamage::class)->findBy(array('medicineConfig' => $config),array('companyName'=>'ASC'));

        $entity = $em->getRepository(\App\Business\Entity\BusinessDamage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }

        $editForm = $this->createEditForm($entity);


        return $this->render('Business/Damage/index.html.twig', array(
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
    private function createEditForm(BusinessDamage $entity)
    {
	    $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
     // TODO(migration): SF3+ takes a form type class-string, not an instance.
     // Move these constructor arguments into BusinessDamageType::configureOptions() and pass them as form options: $config), $entity, array(
    	$form = $this->createForm(new BusinessDamageType($config), $entity, array(
            'action' => $this->generateUrl('business_damage_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing Damage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessDamage::class)->findBy(array('medicineConfig' => $config),array('companyName'=>'ASC'));

        $entity = $em->getRepository(\App\Business\Entity\BusinessDamage::class)->find($id);

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
            return $this->redirect($this->generateUrl('business_damage'));
        }
        return $this->render('Business/Damage/index.html.twig', array(
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
        $entity = $em->getRepository(\App\Business\Entity\BusinessDamage::class)->find($id);
        $stock = $entity->getBusinessParticular();
        $em->remove($entity);
        $em->flush();
        $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateRemoveStockQuantity($stock,'damage');
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('business_damage'));
    }

	public function approvedAction($id)
	{
		$em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
		$damage = $em->getRepository(\App\Business\Entity\BusinessDamage::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
		if (!empty($damage) and ($damage->getProcess() == 'Created')) {
			$em = $this->em;
			$damage->setProcess('Approved');
			$em->flush();
			$em->getRepository(\App\Accounting\Entity\Transaction::class)->insertGlobalDamageTransaction($this->getUser()->getGlobalOption(),$damage);
			return new Response('success');
		} else {
			return new Response('failed');
		}
		exit;
	}

}

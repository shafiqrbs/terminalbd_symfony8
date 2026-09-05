<?php

namespace App\Restaurant\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Restaurant\Entity\Particular;
use App\Restaurant\Form\AccessoriesType;
use App\Restaurant\Form\StockType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * StockController controller.
 *
 */
class StockController extends AbstractController
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
        $entities = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getAccessoriesParticular($config,$data);
        $pagination = $this->paginate($entities);
        $categories = $this->em->getRepository(\App\Restaurant\Entity\Category::class)->findBy(array('restaurantConfig'=>$config,'status'=>1),array('name'=>"ASC"));
        $entity = new Particular();
        $form = $this->createCreateForm($entity);
        return $this->render('Restaurant/Stock/index.html.twig', array(
            'pagination' => $pagination,
            'categories' => $categories,
            'entity' => $entity,
            'formShow' => 'hide',
            'searchForm' => $data,
            'form'   => $form->createView(),
        ));

    }

    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entities = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getAccessoriesParticular($config);
        $pagination = $this->paginate($entities);
        $entity = new Particular();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->em;
            $entity->setRestaurantConfig($config);
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_stock'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Required field does not input"
        );
        $categories = $this->em->getRepository(\App\Restaurant\Entity\Category::class)->findBy(array('restaurantConfig' => $config,'status' => 1),array('name'=>"ASC"));
        return $this->render('Restaurant/Stock/index.html.twig', array(
            'entity' => $entity,
            'pagination' => $pagination,
            'categories' => $categories,
            'formShow'   => 'show',
            'form'       => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Particular $entity)
    {
        $global = $this->getUser()->getGlobalOption();
        $form = $this->createForm(StockType::class, $entity, array('action' => $this->generateUrl('restaurant_stock_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $global));
        return $form;

    }


    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->findOneBy(array('restaurantConfig'=>$config,'id'=>$id));
        $entities = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getAccessoriesParticular($config);
        $pagination = $this->paginate($entities);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        $categories = $this->em->getRepository(\App\Restaurant\Entity\Category::class)->findBy(array('restaurantConfig'=> $config,'status' => 1),array('name' => "ASC"));

        return $this->render('Restaurant/Stock/index.html.twig', array(
            'pagination'        => $pagination,
            'entity'            => $entity,
            'categories'        => $categories,
            'formShow'          => 'show',
            'form'              => $editForm->createView(),
        ));
    }


    /**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Particular $entity)
    {
        $global = $this->getUser()->getGlobalOption();
        $form = $this->createForm(StockType::class, $entity, array('action' => $this->generateUrl('restaurant_stock_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $global));
        return $form;
    }
    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]
    public function updateAction(Request $request, $id)
    {

        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entities = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getAccessoriesParticular($config);
        $pagination = $this->paginate($entities);
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if($entity->upload() && !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->updateProductionElementPrice($entity,$entity->getPurchasePrice());
            $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->updateProductionPrice($config->getId());
            return $this->redirect($this->generateUrl('restaurant_stock'));
        }
        $categories = $this->em->getRepository(\App\Restaurant\Entity\Category::class)->findBy(array('restaurantConfig'=>$config,'status'=>1),array('name'=>"ASC"));
        return $this->render('Restaurant/Stock/index.html.twig', array(
            'pagination'      => $pagination,
            'entity'      => $entity,
            'categories'      => $categories,
            'formShow'            => 'show',
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->findOneBy(array('restaurantConfig' => $config,'id'=>$id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('restaurant_stock'));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Particular $entity)
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
        return $this->redirect($this->generateUrl('restaurant_stock'));
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        $setField = 'set'.$data['name'];
        if($setField == "Price"){
            $price = abs($data['value']);
            $entity->setPrice($price);
        }elseif($data['name'] == "PurchasePrice") {
            $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->updateProductionElementPrice($entity, $price);
            $config = $entity->getRestaurantConfig()->getId();
            $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->updateProductionPrice($config);
        }else{
            $price = $data['value'];
            $entity->$setField($price);
        }
        $em->flush();
        return new Response('success');

    }


    public function productionUpdateAction()
    {
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig()->getId();
        $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->updateProductionPrice($config);
        return new Response('success');

    }

    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function copyAction($id)
    {
        $em = $this->em;
        /* @var $entity Particular */
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->find($id);
        $newEntity = new Particular();
        $em = $this->em;
        $newEntity->setRestaurantConfig($entity->getRestaurantConfig());
        $newEntity->setName($entity->getName());
        $newEntity->setCategory($entity->getCategory());
        $newEntity->setProductionType($entity->getProductionType());
        $newEntity->setService($entity->getService());
        $newEntity->setUnit($entity->getUnit());
        $newEntity->setPrice($entity->getPrice());
        $newEntity->setPurchasePrice($entity->getPurchasePrice());
        $em->persist($newEntity);
        $em->flush();
        $this->em->getRepository(\App\Restaurant\Entity\ProductionElement::class)->copyElement($newEntity,$entity);
        return $this->redirect($this->generateUrl('restaurant_production_edit',array('id'=>$newEntity->getId())));
    }


}

<?php

namespace App\Medicine\Controller;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicineStockHouse;
use App\Medicine\Entity\MedicineStockHouseItem;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Entity\MedicineParticular;
use App\Medicine\Entity\MedicineVendor;
use App\Medicine\Form\HouseStockInType;
use App\Medicine\Form\HouseStockOutType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * StockHouse controller.
 *
 */
class StockHouseController extends AbstractController
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
     * Lists all Vendor entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStockHouse::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        $stockHouses = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('particularType' => 10));
        return $this->render('Medicine/StockHouse/index.html.twig', array(
            'entities' => $pagination,
            'stockHouses' => $stockHouses,
            'searchForm' => $data,
        ));
    }

    /**
     * Lists all Vendor entities.
     *
     */
    public function stockDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStockHouse::class)->findStockDetails($config,$data);
        $pagination = $this->paginate($entities);
        $stockHouses = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('particularType' => 10));
        return $this->render('Medicine/StockHouse/stockDetails.html.twig', array(
            'entities' => $pagination,
            'stockHouses' => $stockHouses,
            'searchForm' => $data,
        ));
    }

    /**
     * Creates a new Vendor entity.
     *
     */
    public function createInAction(Request $request) {

        $entity = new MedicineStockHouse();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig' => $config),array('particularType'=>'ASC'));
        $form = $this->createStockInForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $entity->setMedicineConfig($config);
            $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($entity->getStockName());
            $entity->setMedicineStock($stock);
            $entity->setStockName($stock->getName());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('medicine_stockhouse'));
        }
        return $this->render('Medicine/StockHouse/stockIn.html.twig', array(
            'entities' => $entities,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function stockInAction()
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = new MedicineStockHouse();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $stockItemForm = $this->createStockInForm( $entity);
        return $this->render('Medicine/StockHouse/stockIn.html.twig', array(
            'entity' => $entity,
            'form' => $stockItemForm->createView(),
        ));
    }

    private function createStockInForm(MedicineStockHouse $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(HouseStockInType::class, $entity, array('action' => $this->generateUrl('medicine_stockhouse_in_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'config' => $config));
        return $form;
    }


    public function stockOutAction()
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = new MedicineStockHouse();
        $stockItemForm = $this->createStockOutForm($entity);
        return $this->render('Medicine/StockHouse/stockOut.html.twig', array(
            'entity' => $entity,
            'form' => $stockItemForm->createView(),
        ));
    }

    /**
     * Creates a new Vendor entity.
     *
     */
    public function createOutAction(Request $request) {

        $entity = new MedicineStockHouse();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createStockOutForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'name' => $entity->getStockName()));
            $remainingQnt = $this->em->getRepository(\App\Medicine\Entity\MedicineStockHouse::class)->getStockRemainingQnt($stock,$entity->getWearHouse());
            if($remainingQnt >= $entity->getStockOut()){
                $em = $this->em;
                $entity->setMedicineConfig($config);
                $entity->setMedicineStock($stock);
                $entity->setStockName($stock->getName());
                $em->persist($entity);
                $em->flush();
                $this->requestStack->getSession()->getFlashBag()->add(
                    'success',"Data has been inserted successfully"
                );
                return $this->redirect($this->generateUrl('medicine_stockhouse'));
            }else{
                $this->requestStack->getSession()->getFlashBag()->add(
                    'error',"Quantity must be less or equal the remaining quantity "
                );
                return $this->render('Medicine/StockHouse/stockOut.html.twig', array(
                    'entity' => $entity,
                    'form' => $form->createView(),
                ));
            }
        }
        return $this->render('Medicine/StockHouse/stockOut.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    private function createStockOutForm(MedicineStockHouse $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(HouseStockOutType::class, $entity, array('action' => $this->generateUrl('medicine_stockhouse_out_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'config' => $config));
        return $form;
    }

    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineStockHouse::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Medicine/StockHouse/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction(MedicineStockHouse $entity)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $remainingQnt = $this->em->getRepository(\App\Medicine\Entity\MedicineStockHouse::class)->getStockRemainingQnt($entity->getMedicineStock(),$entity->getWearHouse());
        if($config == $entity->getMedicineConfig()->getId() and $remainingQnt >= $entity->getStockIn()){
            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been deleted successfully"
            );
        }
        return $this->redirect($this->generateUrl('medicine_stockhouse_details'));
    }

    public function autoSearchAction()
    {
       $item = trim($_REQUEST['q']);
       if ($item) {
           $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStockHouse::class)->searchAutoStock($item,$config);
        }
        return new JsonResponse($item);
    }

}

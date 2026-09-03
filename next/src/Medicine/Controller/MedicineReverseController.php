<?php

namespace App\Medicine\Controller;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
/**
 * Vendor controller.
 *
 */
class MedicineReverseController extends AbstractController
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
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->findAll();
        $pagination = $this->paginate($entities);
        return $this->render('Medicine/MedicineReverse/index.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }

    public function reversePurchaseAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->findOneBy(['medicineConfig' => $config, 'medicinePurchase' => $id]);
        return $this->render("Medicine/MedicineReverse/purchase.html.twig", [
            'entity' => $entity,
        ]);
    }

	public function reverseSalesAction($id)
	{
		$config = $this->getUser()->getGlobalOption()->getMedicineConfig();
		$entity = $this->em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->findOneBy(['medicineConfig' => $config, 'medicineSales' => $id]);
		return $this->render("Medicine/MedicineReverse/sales.html.twig", [
			'entity' => $entity,
		]);
	}
}

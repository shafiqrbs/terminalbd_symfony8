<?php

namespace App\Business\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
/**
 * Vendor controller.
 *
 */
class BusinessReverseController extends AbstractController
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
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessReverse::class)->findAll();
        $pagination = $this->paginate($entities);
        return $this->render('Business/Reverse/index.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }

    public function showAction($id)
    {

    	$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessReverse::class)->findOneBy(['businessConfig' => $config, 'id' => $id]);
        if(empty($entity)){
            throw $this->createNotFoundException('Unable to find reverse entity.');
        }
        $twig = 'sales';
        if($entity->getProcess() == 'purchase'){
          $twig = 'purchase';
        }
        return $this->render("Business/Reverse/{$twig}.html.twig", [
            'entity' => $entity,
        ]);
    }
}

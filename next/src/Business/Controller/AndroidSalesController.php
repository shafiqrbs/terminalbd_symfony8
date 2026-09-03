<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Business\Entity\BusinessAndroidProcess;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * Courier controller.
 *u
 */
class AndroidSalesController extends AbstractController
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
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $conf = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessAndroidProcess::class)->getAndroidSalesList($conf,"sales");
        $pagination = $this->paginate($entities);
        return $this->render('Business/AndroidProcess/index.html.twig', [
            'entities' => $pagination,
        ]);
    }


    public function androidProcessMysqlAction(BusinessAndroidProcess $android){
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->em;
        $status = $this->em->getRepository(\App\Business\Entity\BusinessAndroidProcess::class)->insertApiSalesManual($config->getGlobalOption(),$android);
        if($status > 0 ){
            $android->setStatus(true);
            $em->persist($android);
            $em->flush();
            return new Response('success');
        }else{
            return new Response('failed');
        }
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(BusinessAndroidProcess $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $this->em->getRepository(BusinessAndroidProcess::class)->removeSalesItem($entity);
        $em->remove($entity);
        $em->flush();
        return new Response(json_encode(['success' => 'success']));
    }


}

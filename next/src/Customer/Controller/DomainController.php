<?php

namespace App\Customer\Controller;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\User\Entity\User;
use App\User\Form\CustomerEditProfileType;
use App\Tool\Entity\GlobalOption;
use App\Tool\Form\DomainSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainController extends AbstractController
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

    public function indexAction(Request $request)
    {

        $em = $this->em;
        $entity = new GlobalOption();
        $form   = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->getActiveDomainList($form);
        $pagination = $this->paginate($entities);
        $shop = $request->request->get('shop');
        if(!empty($globalOption)){
            $globalOption = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(array('slug' => $shop));
        }else{
            $globalOption ='';
        }

        return $this->render('Customer/Domain/index.html.twig', array(
            'entities' => $pagination,
            'user' => $this->getUser(),
            'form'   => $form->createView(),
            'entity' => $entity,
            'globalOption' => $globalOption,

        ));


    }

    public function domainBookmarkAction($generated)
    {

    }

    /**
     * Creates a form to create a User entity.
     *
     * @param User $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */

    private function createCreateForm(GlobalOption $entity)
    {
        $em = $this->em->getRepository(\App\Tool\Entity\Syndicate::class);
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(DomainSearchType::class, $entity, array('action' => $this->generateUrl('customer_domain'),
            'method' => 'GET',
            'attr' => array(
                'id' => '',
                'novalidate' => 'novalidate',
            ), 'em' => $em, 'location' => $location));
        return $form;
    }

   



}

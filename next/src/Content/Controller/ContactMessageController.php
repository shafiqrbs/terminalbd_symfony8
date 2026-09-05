<?php

namespace App\Content\Controller;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Tool\Event\SmsEvent;
use App\Tool\Event\EmailEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContactMessage controller.
 *
 */
class ContactMessageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }



    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            20  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all ContactMessage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $user = $this->getUser();

        $entities = $em->getRepository(\App\Content\Entity\ContactMessage::class)->findBy(['user'=> $user ,'archive' => 0 ],['created' => 'desc']);
        $entities = $this->paginate($entities);
        return $this->render('Content/ContactMessage/index.html.twig', [
            'pagination' => $entities,
        ]);
    }

    /**
     * Lists all ContactMessage entities.
     *
     */
    public function archiveAction()
    {
        $em = $this->em;

        $user = $this->getUser();

        $entities = $em->getRepository(\App\Content\Entity\ContactMessage::class)->findBy(['user'=> $user ,'archive' => 1],['created' => 'desc']);
        $entities = $this->paginate($entities);
        return $this->render('Content/ContactMessage/archive.html.twig', [
            'pagination' => $entities,
        ]);
    }


    /**
     * Edits an existing ContactMessage entity.
     *
     */
    public function updateAction(Request $request)
    {
        $data = $request ->request->all();
        $this->em->getRepository(\App\Content\Entity\ContactMessage::class)->reply($data);
        exit;

    }

    public function deleteAction($id){

        $em = $this->em;
        $user = $this->getUser();

        $entity = $this->em->getRepository(\App\Content\Entity\ContactMessage::class)->findOneBy(['user' => $user,'id' => $id]);

        $dispatcher = $this->eventDispatcher;
        $dispatcher->dispatch(new SmsEvent($entity), 'setting_tool.post.sms_message');
        $dispatcher->dispatch(new EmailEvent($entity), 'setting_tool.post.email_message');



        $em->remove($entity);
        $em->flush();

        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been deleted successfully"
        );

        return $this->redirect($this->generateUrl('contactmessage_archive'));
    }


}

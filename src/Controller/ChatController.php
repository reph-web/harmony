<?php

namespace App\Controller;

use App\Entity\Messages;
use App\Form\SendMessageType;
use App\Repository\ChatsRepository;
use App\Repository\MessagesRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    /**
     * @Route("/chat", name="app_chat")
     */
    public function index(ChatsRepository $chatRepo, MessagesRepository $messagesRepo, Request $request, EntityManagerInterface $entityManager): Response
    {
        if( $this->getUser() ){
            $message = new Messages();

            //Creat Chat Form
            $SendMessageForm = $this->createForm(SendMessageType::class, $message);
            $SendMessageForm->handleRequest($request);  
            if ($SendMessageForm->isSubmitted() && $SendMessageForm->isValid()) {

                $message->setAuthor($this->getUser());
                $message->setChat($chatRepo->find(1));
                $message->setTimestamp(new \DateTime());
                $message->setIsImg(false);
                $message->setIsVid(false);

                $entityManager->persist($message);
                $entityManager->flush();
                $this->addFlash('success', 'msg created!');
            }
            return $this->render('chat/index.html.twig', [
                'controller_name' => 'ChatController',
                'chat' => $chatRepo->find(1),
                'messages' => $messagesRepo->findAll(),
                'messageForm' => $SendMessageForm->createView()
            ]);
        }else{
            return $this->redirectToRoute('app_login');
        }
    }
    /**
     * @Route("/delete-message/{id}", name="app_deleteMessage")
     */
    public function deleteMessage($id, MessagesRepository $messagesRepo, EntityManagerInterface $entityManager): Response
    {
        $message = $messagesRepo->find($id);
        if( $this->getUser() ==  $message->getAuthor() || $this->isGranted('ROLE_ADMIN')){
            $this->addFlash('success', 'Message deleted!');
            $entityManager->remove($message);
            $entityManager->flush($message);
            return $this->redirectToRoute('app_chat');
        }else{
            $this->addFlash('danger', 'Forbidden');
            return $this->redirectToRoute('app_chat');
        }
    }

}

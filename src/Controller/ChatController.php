<?php

namespace App\Controller;

use App\Entity\Messages;
use App\Entity\Chats;
use App\Entity\Users;
use App\Form\SendMessageType;
use App\Repository\ChatsRepository;
use App\Repository\MessagesRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    /**
     * @Route("/chat/{chatname}", defaults={"chatname"="general"}, name="app_chat")
     */
    public function index($chatname, ChatsRepository $chatRepo, MessagesRepository $messagesRepo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $chat = $chatRepo->findOneBy(["name"=>$chatname]);

        if($this->getUser()){
            if($chat){
                $user = $this->getUser();
                $hasRole = false;
                foreach ($user->getRoles() as $role){
                    if(in_array($role, $chat->getRolesAuth())){
                        $hasRole = true;
                    }
                }

                if($hasRole){
                    $message = new Messages();
                    //Creat Chat Form
                    $SendMessageForm = $this->createForm(SendMessageType::class, $message);
                    $SendMessageForm->handleRequest($request);  
                    if ($SendMessageForm->isSubmitted() && $SendMessageForm->isValid()) {

                        $message->setAuthor($this->getUser());
                        $message->setChat($chat);
                        $message->setTimestamp(new \DateTime());
                        $message->setIsImg(false);
                        $message->setIsVid(false);

                        $entityManager->persist($message);
                        $entityManager->flush();
                    }
                    return $this->render('chat/index.html.twig', [
                        'controller_name' => 'ChatController',
                        'chat' => $chat,
                        'messages' => $messagesRepo->findAll(),
                        'messageForm' => $SendMessageForm->createView()
                    ]);
                }else{
                    $this->addFlash('danger', 'You don\'t have access to this chat');
                    return $this->redirectToRoute('app_home');
                }
            }else{
                $this->addFlash('danger', "This chat doesn't exist");
                return $this->redirectToRoute('app_home');
            }
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

    /**
     * @Route("/update/{sentChatId}/{lastMsgId}", name="app_update")
     */
    public function update($sentChatId, $lastMsgId, MessagesRepository $messagesRepo, EntityManagerInterface $entityManager): Response
    {
        $lastRetrievedMsg  = $messagesRepo->retrieveLastMsg($sentChatId, $lastMsgId);
        $cleanedResult = [];
        foreach($lastRetrievedMsg as $msg){
            $newEntry = [
                'id' => $msg->getId(),
                'author' => $msg->getAuthor()->getUserIdentifier(),
                'authorRoles' => $msg->getAuthor()->getRoles(),
                'authorAvatar' => $msg->getAuthor()->getAvatar(),
                'content' => $msg->getContent(),
                'chat' => $msg->getChat(),

            ];
            array_push($cleanedResult, $newEntry);
        }
        if(!$cleanedResult){
            return new JsonResponse(null);
        }
        return $this->render('chat/update.html.twig', [
            'controller_name' => 'ChatController',
            'messages' => $cleanedResult,
            'chatId' => $sentChatId
        ]);
    }

}

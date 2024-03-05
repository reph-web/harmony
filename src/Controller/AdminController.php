<?php

namespace App\Controller;

use App\Entity\Chats;
use App\Form\CreateChatType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="app_admin")
     */
    public function index(UsersRepository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {
        
        if($this->isGranted('ROLE_ADMIN')){
            $nbOfUsers = $repo->count([]);
            $chat = new Chats();
            //Creat Chat Form
            $createChatForm = $this->createForm(CreateChatType::class, $chat);
            $createChatForm->handleRequest($request);
            if ($createChatForm->isSubmitted() && !($createChatForm->isValid())) {
                $chatnameSubmitted = $chat->getName();
                if($repo->findOneBy(["name"=>$chatnameSubmitted])){
                    $this->addFlash('danger', 'This chat already exist');
                }
            }          
            if ($createChatForm->isSubmitted() && $createChatForm->isValid()) {
                $entityManager->persist($chat);
                $entityManager->flush();
                $this->addFlash('success', 'Chat created!');
                return $this->redirectToRoute("app_admin",array('name'=> $chat->getName()));
            }

            return $this->render('admin/index.html.twig', [
                'controller_name' => 'AdminController',
                'nbOfUsers' => $nbOfUsers,
                'createChatForm' => $createChatForm->createView(),

            ]);
        }
        return $this->render('admin/notadmin.html.twig', [
            'controller_name' => 'AdminController',
        ]);

    }

    /**
     * @Route("/admin/userlist", name="app_userList")
     */
    public function userlist(UsersRepository $repo): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
            return $this->render('admin/userlist.html.twig', [
                'controller_name' => 'AdminController',
                'users' => $repo->findAll()
            ]);
        }
        return $this->render('admin/notadmin.html.twig', [
            'controller_name' => 'AdminController',
        ]);

    }
}

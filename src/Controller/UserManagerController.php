<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\EditBioType;
use App\Form\EditUsernameType;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserManagerController extends AbstractController
{
    /**
     * @Route("/user/{username?}", name="app_profilePage")
     */
    public function profilePage($username, Request  $request, UsersRepository $repo, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_frontpage');
        }
        if(!$username){
            return $this->redirectToRoute('app_home');
        }

        $userlogged = $this->getUser()->getUserIdentifier();
        $user_info = $repo->findOneBy(["username"=>$username]); //username is unique

        if($this->isGranted('ROLE_ADMIN') || $userlogged == $username){
            $user = $repo->findOneBy(["username"=>$username]);
            

            //Edit username Form
            $editUsernameForm = $this->createForm(EditUsernameType::class, $user);
            $editUsernameForm->handleRequest($request);
            if ($editUsernameForm->isSubmitted() && !($editUsernameForm->isValid())) {
                $usernameSubmitted = $user->getUserIdentifier();
                if($repo->findOneBy(["username"=>$usernameSubmitted])){
                    $this->addFlash('danger', 'Username already taken');
                }
            }          
            if ($editUsernameForm->isSubmitted() && $editUsernameForm->isValid()) {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Username updated!');
                return $this->redirectToRoute("app_profilePage",array('username'=> $user->getUserIdentifier()));
            }

            //Edit Bio Form
            $editBioForm = $this->createForm(EditBioType::class, $user);
            $editBioForm->handleRequest($request);
            if ($editBioForm->isSubmitted() && $editBioForm->isValid()) {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Bio updated!');
            }

            //Edit Avatar Form
            $editAvatarForm = $this->createForm(EditBioType::class, $user);
            $editAvatarForm->handleRequest($request);
            if ($editAvatarForm->isSubmitted() && $editAvatarForm->isValid()) {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Avatar updated!');
            }
            $passingVar = [
                'controller_name' => 'UserManagerController',
                'userInfo' => $user_info,
                'editUsernameForm' => $editUsernameForm->createView(),
                'editBioForm' => $editBioForm->createView(),
                'editAvatarForm' => $editAvatarForm->createView(),
            ];
        }else{
            $passingVar = [
                'controller_name' => 'UserManagerController',
                'userInfo' => $user_info,
            ];
        }
        
        return $this->render('user_manager/profile.html.twig', $passingVar);
    }

    /**
     * @Route("/delete-user/{username?}", name="app_deleteUser")
     */
    public function deleteUser($username, UsersRepository $repo, EntityManagerInterface $entityManager, Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
            $user_info = $repo->findOneBy(["username"=>$username]); //username is unique

            if (!$user_info) { // if username not found in database
                $this->addFlash('danger', 'User not found');
                return $this->redirectToRoute("app_admin");
            };

            $user_roles = $user_info->getRoles();
            if(in_array("ROLE_ADMIN", $user_roles)){ // if username is an admin
                $this->addFlash('danger', 'Admin can\'t be deleted');
                $route = $request->headers->get('referer');
                return $this->redirectToRoute($route);
            }

            $entityManager->remove($user_info);
            $entityManager->flush($user_info);
            $this->addFlash('success', 'User deleted');
            $route = $request->headers->get('referer');
            return $this->redirectToRoute($route);
        }

        $this->addFlash('danger', 'You are not ADMIN !!');
        return $this->redirectToRoute("app_home");
    }
}

<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\AddRoleType;
use App\Form\EditAvatarType;
use App\Form\EditBioType;
use App\Form\EditUsernameType;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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

            //Edit Username Form
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

            //Add Role Form
            $addRoleForm = $this->createForm(AddRoleType::class);
            $addRoleForm->handleRequest($request);
            if ($addRoleForm->isSubmitted() && $addRoleForm->isValid()) {
                    $data = $addRoleForm->getData();
                    $chosenRole = $data['roles'];

                    $oldRoles = $user->getRoles();
                    $newRoles = array_merge($oldRoles, $chosenRole);
                    $user->setRoles(array_unique($newRoles));

                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('success', 'Role added');
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
            $editAvatarForm = $this->createForm(EditAvatarType::class, $user);
            $editAvatarForm->handleRequest($request);

            if ($editAvatarForm->isSubmitted() && $editAvatarForm->isValid()) {
                $avatarErrors = $editAvatarForm->getErrors();
                foreach($avatarErrors as  $error) { 
                    $this->addFlash("danger", $error->getMessage());
                }
                /** @var UploadedFile $avatarFile */
                $avatarFile = $editAvatarForm->get('avatar')->getData();
                if ($avatarFile) {
                    // this is needed to safely include the file name as part of the URL
                    $newFilename = 'avatar-'.uniqid().'.'.$avatarFile->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $avatarFile->move(
                            $this->getParameter('avatar_directory'),
                            $newFilename
                        );
                        $user->setAvatar($newFilename);
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash('success', 'Avatar updated!');
                        return $this->redirectToRoute("app_profilePage",array('username'=> $user->getUserIdentifier()));
                    }catch (FileException $e) {
                        $this->addFlash('danger', 'Upload failed : '.$e);
                    }
                }
            }
            $passingVar = [
                'controller_name' => 'UserManagerController',
                'userInfo' => $user_info,
                'editUsernameForm' => $editUsernameForm->createView(),
                'editBioForm' => $editBioForm->createView(),
                'editAvatarForm' => $editAvatarForm->createView(),
                'addRoleForm' => $addRoleForm->createView(),
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
            return $this->redirect($route);
        }

        $this->addFlash('danger', 'You are not ADMIN !!');
        return $this->redirectToRoute("app_home");
    }

    /**
     * @Route("/delete-roles/{username?}", name="app_deleteRoles")
     */
    public function deleteRoles($username, UsersRepository $repo, EntityManagerInterface $entityManager, Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
            $user_info = $repo->findOneBy(["username"=>$username]); //username is unique

            if (!$user_info) { // if username not found in database
                $this->addFlash('danger', 'User not found');
                return $this->redirectToRoute("app_admin");
            };

            $user_info->setRoles([]);

            $entityManager->persist($user_info);
            $entityManager->flush($user_info);
            $this->addFlash('success', 'Roles deleted');
            $route = $request->headers->get('referer');
            return $this->redirect($route);
        }

        $this->addFlash('danger', 'You are not ADMIN !!');
        return $this->redirectToRoute("app_home");
    }
}

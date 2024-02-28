<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="app_admin")
     */
    public function index(): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
            return $this->render('admin/index.html.twig', [
                'controller_name' => 'AdminController',
            ]);
        }
        return $this->render('admin/notadmin.html.twig', [
            'controller_name' => 'AdminController',
        ]);

    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
<<<<<<< HEAD
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
=======
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
>>>>>>> eabe32bd1d39cea1a5a722f0ae36928346b48e11

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
<<<<<<< HEAD
        // Redirect to the user index as the default homepage
        return $this->redirectToRoute('app_user_index');
    }
}
=======
        return $this->render('home/index.html.twig');
    }
}
>>>>>>> eabe32bd1d39cea1a5a722f0ae36928346b48e11

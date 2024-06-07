<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class IndexController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig',);
    }
//use Symfony\Component\Templating\EngineInterface;
    #[Route('/{template}', name: 'template')]
    public function template(Environment $twig, string $template) : Response
    {
        $loader = $twig->getLoader();
        if ($loader->exists("index/$template")) {
            $answer = "template $template exists";
        } else {
            $answer = "no such template '$template' exists";
        }
        return $this->render('index/template.html.twig',[
            'template' => $template, 'answer' => $answer
        ]);
    }

}

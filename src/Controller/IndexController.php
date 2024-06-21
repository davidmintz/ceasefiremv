<?php

namespace App\Controller;

use App\Service\Standout;
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

    #[Route('/actions', name: 'actions')]
    public function actions(): Response
    {
        $start_time = $this->getParameter('app.standout_start_time');
        $standout = new Standout($start_time);
        return $this->render('index/actions.html.twig',[

            'template' => 'actions', // get rid of this
            'start_time' => $start_time,
            'standout' => $standout
        ]);
    }
//use Symfony\Component\Templating\EngineInterface;
    #[Route('/{template}', name: 'template')]
    public function template(Environment $twig, string $template) : Response
    {
        $loader = $twig->getLoader();
        if ($loader->exists("index/$template.html.twig")) {
            //$answer = "template $template exists";
            return $this->render("index/$template.html.twig",['template' => $template]);
        } else {
            $answer = "no such template '$template' exists";
            return $this->render('index/template.html.twig',[
                'template' => $template, 'answer' => $answer
            ]);
        }
    }

}

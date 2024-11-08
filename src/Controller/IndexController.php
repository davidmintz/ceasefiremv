<?php

namespace App\Controller;

use App\Service\Standout;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class IndexController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig',);
    }
    #[Route('/privacy-consent', name: 'privacy_consent')]
    public function accept_privacy_policy(): Response
    {
        // experimental
        //$request->cookies->set('PRIVACY_CONSENT',true);
        $cookie = Cookie::create('PRIVACY_CONSENT')
            ->withValue("1") // maybe make it the version number?
            ->withExpires(strtotime('+2 year'));
        $response = new Response();
//        $response->headers->clearCookie('PRIVACY_CONSENT');
        $response->headers->setCookie($cookie);//Cookie($cookie);
        //$response->setContent($this->renderView('index/index.html.twig',['template' => 'index']));
        return $response;
    }

    #[Route('/actions', name: 'actions')]
    public function actions(): Response
    {

        $standout = new Standout($this->getParameter("app.standout"));
        return $this->render('index/actions.html.twig',[
            'standout' => $standout
        ]);
    }

    #[Route('/1948', name: '1948')]
    public function screening_1948(): Response
    {
        return $this->redirect('/actions#1948');
    }
//use Symfony\Component\Templating\EngineInterface;
    #[Route('/{template}', name: 'template')]
    public function template(Environment $twig, string $template) : Response
    {
        $loader = $twig->getLoader();
        if ($loader->exists("index/$template.html.twig")) {

            return $this->render("index/$template.html.twig",['template' => $template]);
        } else {
            $answer = "no such template '$template' exists";
            return $this->render('index/template.html.twig',[
                'template' => $template, 'answer' => $answer
            ]);
        }
    }

}

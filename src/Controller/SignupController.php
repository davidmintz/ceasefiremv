<?php
namespace App\Controller;

use App\Form\SignUpFormType;
use App\Entity\SignUpRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SignupController extends AbstractController
{
    // Route for the sign-up form
    #[Route('/signup', name: 'signup', methods: ['POST'])]
    public function signup(Request $request): Response
    {
        // Create the form and handle the request

        $form = $this->createForm(SignUpFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Do something, for now just a comment
            // For example: save the data, send an email, etc.
            // Comment out the actual processing for now

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Form submitted successfully!',
                'data' => 'yadda yadda' // You can replace this with the actual data you're working with
            ]);
        }

        // If form is invalid, return the rendered form with validation errors as HTML
        return new Response(
            $this->renderView('signup/_form.html.twig', [
                'form' => $form->createView(),
            ]),
            422, // Unprocessable Entity status code for validation errors
            ['Content-Type' => 'text/html']
        );
    }

}


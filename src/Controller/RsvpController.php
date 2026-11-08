<?php

namespace App\Controller;

use App\Entity\Rsvp;
use App\Form\RsvpType;
use App\Repository\RsvpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Double opt-in RSVP. Routes have priority 1 so they win over
 * IndexController's catch-all /{template} route.
 */
#[Route('/rsvp', priority: 1)]
class RsvpController extends AbstractController
{
    public function __construct(
        private readonly RsvpRepository $repo,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('', name: 'rsvp', methods: ['GET', 'POST'])]
    public function form(Request $request, MailerInterface $mailer): Response
    {
        $meeting = $this->meeting();
        if ($this->isClosed()) {
            return $this->render('rsvp/closed.html.twig', ['meeting' => $meeting]);
        }

        $form = $this->createForm(RsvpType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // honeypot tripped: act as if it worked, store nothing
            if (!empty($data['website'])) {
                $this->logger->info('RSVP honeypot triggered', ['ip' => $request->getClientIp()]);

                return $this->redirectToRoute('rsvp_check_email');
            }

            $rsvp = $this->repo->findOneByMeetingAndEmail($meeting['meeting'], $data['email'])
                ?? new Rsvp($meeting['meeting'], $data['email']);
            $rsvp->update($data['name'], $data['mode']);
            $token = $rsvp->issueToken($this->deadline());
            $this->em->persist($rsvp);
            $this->em->flush();

            try {
                $this->sendConfirmation($mailer, $rsvp, $token);
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('RSVP confirmation email failed', ['email' => $rsvp->getEmail(), 'error' => $e->getMessage()]);
                $this->addFlash('danger', 'Sorry, we could not send the confirmation email just now. Please try again in a few minutes.');

                return $this->render('rsvp/form.html.twig', ['form' => $form, 'meeting' => $meeting]);
            }

            return $this->redirectToRoute('rsvp_check_email');
        }

        return $this->render('rsvp/form.html.twig', ['form' => $form, 'meeting' => $meeting]);
    }

    #[Route('/check-email', name: 'rsvp_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('rsvp/check_email.html.twig', ['meeting' => $this->meeting()]);
    }

    /**
     * GET shows a Confirm button; only the POST marks the RSVP verified, so
     * link-scanning mail filters can't confirm on the recipient's behalf.
     */
    #[Route('/confirm/{token}', name: 'rsvp_confirm', requirements: ['token' => '[A-Za-z0-9_-]{43}'], methods: ['GET', 'POST'])]
    public function confirm(Request $request, string $token): Response
    {
        $meeting = $this->meeting();
        $rsvp = $this->repo->findOneByRawToken($token);

        if (!$rsvp || $rsvp->isTokenExpired()) {
            return $this->render('rsvp/invalid.html.twig', ['meeting' => $meeting], new Response('', Response::HTTP_NOT_FOUND));
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('rsvp_confirm', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }
            $rsvp->verify();
            $this->em->flush();

            return $this->render('rsvp/confirmed.html.twig', ['meeting' => $meeting, 'rsvp' => $rsvp]);
        }

        return $this->render('rsvp/confirm.html.twig', ['meeting' => $meeting, 'rsvp' => $rsvp, 'token' => $token]);
    }

    private function sendConfirmation(MailerInterface $mailer, Rsvp $rsvp, string $token): void
    {
        $meeting = $this->meeting();
        $email = (new TemplatedEmail())
            ->from(new Address($meeting['from'], 'MV For Palestine'))
            ->to(new Address($rsvp->getEmail(), $rsvp->getName()))
            ->subject('Please confirm your RSVP for '.$meeting['when'])
            ->textTemplate('email/rsvp_confirm.txt.twig')
            ->htmlTemplate('email/rsvp_confirm.html.twig')
            ->context([
                'rsvp' => $rsvp,
                'meeting' => $meeting,
                'url' => $this->generateUrl('rsvp_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $mailer->send($email);
    }

    /** @return array{meeting: string, title: string, when: string, deadline: string, from: string} */
    private function meeting(): array
    {
        return $this->getParameter('app.rsvp');
    }

    private function deadline(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->meeting()['deadline']);
    }

    private function isClosed(): bool
    {
        return new \DateTimeImmutable() > $this->deadline();
    }
}

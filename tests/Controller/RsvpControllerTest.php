<?php

namespace App\Tests\Controller;

use App\Entity\Rsvp;
use App\Enum\AttendanceMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RsvpControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($this->em);
        $meta = [$this->em->getClassMetadata(Rsvp::class)];
        $tool->dropSchema($meta);
        $tool->createSchema($meta);
    }

    private function submit(string $name, string $email, string $mode = 'zoom', string $website = ''): void
    {
        $crawler = $this->client->request('GET', '/rsvp');
        $form = $crawler->selectButton('RSVP')->form([
            'rsvp[mode]' => $mode,
            'rsvp[name]' => $name,
            'rsvp[email]' => $email,
            'rsvp[website]' => $website,
        ]);
        $this->client->submit($form);
    }

    private function confirmUrlFromLastEmail(): string
    {
        $body = $this->getMailerMessage()->getTextBody();
        $this->assertMatchesRegularExpression('~(https?://\S+/rsvp/confirm/[A-Za-z0-9_-]{43})~', $body);
        preg_match('~(https?://\S+/rsvp/confirm/[A-Za-z0-9_-]{43})~', $body, $m);

        // the HTML part must carry the same link as a clickable href
        $this->assertStringContainsString('href="'.$m[1].'"', $this->getMailerMessage()->getHtmlBody());

        return $m[1];
    }

    private function findRsvps(): array
    {
        $this->em->clear();

        return $this->em->getRepository(Rsvp::class)->findAll();
    }

    public function testFullDoubleOptInFlow(): void
    {
        $this->submit('Jane Doe', 'Jane@Example.org');
        $this->assertResponseRedirects('/rsvp/check-email');
        $this->assertEmailCount(1);

        $rsvps = $this->findRsvps();
        $this->assertCount(1, $rsvps);
        $this->assertSame('jane@example.org', $rsvps[0]->getEmail());
        $this->assertSame(AttendanceMode::Zoom, $rsvps[0]->getMode());
        $this->assertFalse($rsvps[0]->isVerified());

        // opening the link confirms
        $this->client->request('GET', $this->confirmUrlFromLastEmail());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Thank you, Jane Doe');
        $this->assertTrue($this->findRsvps()[0]->isVerified());
    }

    public function testResubmitUpdatesSameRowAndInvalidatesOldLink(): void
    {
        $this->submit('Jane Doe', 'jane@example.org', 'zoom');
        $oldUrl = $this->confirmUrlFromLastEmail();

        $this->submit('Jane Q. Doe', 'JANE@example.org', 'in_person');
        $this->assertResponseRedirects('/rsvp/check-email');

        $rsvps = $this->findRsvps();
        $this->assertCount(1, $rsvps);
        $this->assertSame('Jane Q. Doe', $rsvps[0]->getName());
        $this->assertSame(AttendanceMode::InPerson, $rsvps[0]->getMode());

        $this->client->request('GET', $oldUrl);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testReopeningLinkShowsConfirmedAgain(): void
    {
        $this->submit('Jane Doe', 'jane@example.org');
        $url = $this->confirmUrlFromLastEmail();
        $this->client->request('GET', $url); // e.g. a mail scanner
        $verifiedAt = $this->findRsvps()[0]->getVerifiedAt();

        $this->client->request('GET', $url); // then the person
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Thank you, Jane Doe');
        $this->assertEquals($verifiedAt, $this->findRsvps()[0]->getVerifiedAt());
    }

    public function testHoneypotStoresNothingAndSendsNothing(): void
    {
        $this->submit('Bot', 'bot@example.org', 'zoom', 'http://spam.example');
        $this->assertResponseRedirects('/rsvp/check-email');
        $this->assertEmailCount(0);
        $this->assertCount(0, $this->findRsvps());
    }

    public function testValidationErrorsRerenderForm(): void
    {
        $this->submit('', 'not-an-email');
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(0, $this->findRsvps());
    }

    public function testBogusTokenIs404(): void
    {
        $this->client->request('GET', '/rsvp/confirm/'.str_repeat('x', 43));
        $this->assertResponseStatusCodeSame(404);
    }
}

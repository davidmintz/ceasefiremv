<?php

namespace App\Entity;

use App\Enum\AttendanceMode;
use App\Repository\RsvpRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RsvpRepository::class)]
#[ORM\Table(name: 'rsvp')]
#[ORM\UniqueConstraint(name: 'uniq_rsvp_meeting_email', columns: ['meeting', 'email'])]
#[ORM\UniqueConstraint(name: 'uniq_rsvp_token_hash', columns: ['token_hash'])]
class Rsvp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** meeting key, e.g. "2026-11-17" */
    #[ORM\Column(length: 32)]
    private string $meeting;

    /** stored lowercased */
    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 60)]
    private string $name = '';

    #[ORM\Column(length: 16, enumType: AttendanceMode::class)]
    private AttendanceMode $mode = AttendanceMode::InPerson;

    /** sha256 of the raw token; the raw token exists only in the email */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $tokenHash = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $tokenExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    public function __construct(string $meeting, string $email)
    {
        $this->meeting = $meeting;
        $this->email = self::normalizeEmail($email);
        $this->createdAt = $this->updatedAt = new \DateTimeImmutable();
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /**
     * Generate a fresh verification token, store its hash, and return the raw
     * token (URL-safe) for inclusion in the email. Any previous token is invalidated.
     */
    public function issueToken(\DateTimeImmutable $expiresAt): string
    {
        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->tokenHash = self::hashToken($raw);
        $this->tokenExpiresAt = $expiresAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $raw;
    }

    public function isTokenExpired(?\DateTimeImmutable $now = null): bool
    {
        return null === $this->tokenExpiresAt || ($now ?? new \DateTimeImmutable()) > $this->tokenExpiresAt;
    }

    public function verify(): void
    {
        $this->verifiedAt ??= new \DateTimeImmutable();
        $this->tokenHash = null;
        $this->tokenExpiresAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(string $name, AttendanceMode $mode): static
    {
        $this->name = trim($name);
        $this->mode = $mode;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getId(): ?int { return $this->id; }
    public function getMeeting(): string { return $this->meeting; }
    public function getEmail(): string { return $this->email; }
    public function getName(): string { return $this->name; }
    public function getMode(): AttendanceMode { return $this->mode; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getVerifiedAt(): ?\DateTimeImmutable { return $this->verifiedAt; }
    public function isVerified(): bool { return null !== $this->verifiedAt; }
}

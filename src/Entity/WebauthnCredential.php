<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

#[ORM\Entity]
#[ORM\Table(name: 'webauthn_credential')]
class WebauthnCredential
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'webauthnCredentials')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private ?string $credentialData = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): ?Uuid { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getCredentialData(): ?string { return $this->credentialData; }
    public function setCredentialData(string $d): self { $this->credentialData = $d; return $this; }

    public function getCredentialSource(): PublicKeyCredentialSource
    {
        $factory = new WebauthnSerializerFactory(AttestationStatementSupportManager::create());
        $serializer = $factory->create();
        return $serializer->deserialize($this->credentialData, PublicKeyCredentialSource::class, 'json');
    }

    public function setCredentialSource(PublicKeyCredentialSource $source): void
    {
        $factory = new WebauthnSerializerFactory(AttestationStatementSupportManager::create());
        $serializer = $factory->create();
        $this->credentialData = $serializer->serialize($source, 'json');
    }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getLastUsedAt(): ?\DateTimeImmutable { return $this->lastUsedAt; }
    public function setLastUsedAt(\DateTimeImmutable $d): self { $this->lastUsedAt = $d; return $this; }
    public function touch(): void { $this->lastUsedAt = new \DateTimeImmutable(); }
}
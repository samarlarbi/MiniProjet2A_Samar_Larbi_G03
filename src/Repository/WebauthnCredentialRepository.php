<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnCredentialRepository extends ServiceEntityRepository implements PublicKeyCredentialSourceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        /** @var WebauthnCredential[] $all */
        $all = $this->findAll();
        foreach ($all as $wc) {
            $source = $wc->getCredentialSource();
            if ($source->publicKeyCredentialId === $publicKeyCredentialId) {
                return $source;
            }
        }
        return null;
    }

    public function findOneCredentialByCredentialId(string $publicKeyCredentialId): ?WebauthnCredential
    {
        /** @var WebauthnCredential[] $all */
        $all = $this->findAll();
        foreach ($all as $wc) {
            $source = $wc->getCredentialSource();
            if ($source->publicKeyCredentialId === $publicKeyCredentialId) {
                return $wc;
            }
        }
        return null;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $all     = $this->findAll();
        $sources = [];
        foreach ($all as $wc) {
            if ((string) $wc->getUser()->getId() === $publicKeyCredentialUserEntity->id) {
                $sources[] = $wc->getCredentialSource();
            }
        }
        return $sources;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $source): void
    {
        $wc = $this->findOneCredentialByCredentialId($source->publicKeyCredentialId);
        if (!$wc) {
            $wc = new WebauthnCredential();
            $wc->setName('Passkey');
        }
        $wc->setCredentialSource($source);
        $this->getEntityManager()->persist($wc);
        $this->getEntityManager()->flush();
    }

    public function findPendingOptionsForUser(User $user): ?array
    {
        return null;
    }
}
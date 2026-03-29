<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;
use Webauthn\Bundle\Service\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class PasskeyAuthService
{
    public function __construct(
        private PublicKeyCredentialCreationOptionsFactory $creationOptionsFactory,
        private PublicKeyCredentialRequestOptionsFactory  $requestOptionsFactory,
        private AuthenticatorAttestationResponseValidator $attestationValidator,
        private AuthenticatorAssertionResponseValidator   $assertionValidator,
        private UserRepository                            $userRepository,
        private WebauthnCredentialRepository              $credentialRepository,
        private EntityManagerInterface                    $em,
        private SerializerInterface                       $serializer,
        private RequestStack                              $requestStack,
    ) {}

    public function getRegistrationOptions(User $user): array
    {
        $userEntity = PublicKeyCredentialUserEntity::create(
            $user->getEmail(),
            (string) $user->getId(),
            $user->getEmail(),
        );

        $options     = $this->creationOptionsFactory->create('default', $userEntity);
        $optionsJson = $this->serializer->serialize($options, 'json');
        $optionsArr  = json_decode($optionsJson, true);

        $this->requestStack->getSession()->set('webauthn_registration_options', $optionsArr);

        return $optionsArr;
    }

    public function verifyRegistration(string $credentialJson, User $user): void
    {
        $optionsArr = $this->requestStack->getSession()->get('webauthn_registration_options')
            ?? throw new \RuntimeException('Options de registration introuvables en session');

        $credential = $this->serializer->deserialize(
            $credentialJson,
            \Webauthn\PublicKeyCredential::class,
            'json'
        );

        $options = $this->serializer->deserialize(
            json_encode($optionsArr),
            PublicKeyCredentialCreationOptions::class,
            'json'
        );

        $source = $this->attestationValidator->check(
            $credential->response,
            $options,
            'http://localhost:8001',
        );

        $wc = new WebauthnCredential();
        $wc->setUser($user);
        $wc->setName('Passkey');
        $wc->setCredentialSource($source);
        $this->em->persist($wc);
        $this->em->flush();

        $this->requestStack->getSession()->remove('webauthn_registration_options');
    }

    public function getLoginOptions(): array
    {
        $options     = $this->requestOptionsFactory->create('default', []);
        $optionsJson = $this->serializer->serialize($options, 'json');
        $optionsArr  = json_decode($optionsJson, true);

        $this->requestStack->getSession()->set('webauthn_login_options', $optionsArr);

        return $optionsArr;
    }

    public function verifyLogin(string $credentialJson): User
    {
        $optionsArr = $this->requestStack->getSession()->get('webauthn_login_options')
            ?? throw new \RuntimeException('Options de login introuvables en session');

        $credential = $this->serializer->deserialize(
            $credentialJson,
            \Webauthn\PublicKeyCredential::class,
            'json'
        );

        $rawId        = $credential->rawId ?? $credential->id ?? '';
        $credentialId = base64_encode($rawId);

        $source = $this->credentialRepository->findOneByCredentialId($credentialId)
            ?? throw new \RuntimeException('Credential introuvable');

        $wc = $this->credentialRepository->findOneCredentialByCredentialId($credentialId)
            ?? throw new \RuntimeException('Credential entity introuvable');

        $options = $this->serializer->deserialize(
            json_encode($optionsArr),
            PublicKeyCredentialRequestOptions::class,
            'json'
        );

        $this->assertionValidator->check(
            $source,
            $credential->response,
            $options,
            'http://localhost:8001',
            null,
        );

        $wc->touch();
        $this->em->flush();

        $this->requestStack->getSession()->remove('webauthn_login_options');

        return $wc->getUser();
    }
}

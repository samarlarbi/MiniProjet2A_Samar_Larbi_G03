<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PasskeyAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface       $jwtManager,
        private readonly RefreshTokenManagerInterface   $refreshManager,
        private readonly RefreshTokenGeneratorInterface $refreshGenerator,
        private readonly EntityManagerInterface         $entityManager,
    ) {}

    #[Route('/register/options', methods: ['POST'])]
    public function registerOptions(Request $request, PasskeyAuthService $passkeyService): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email requis'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        try {
            return $this->json($passkeyService->getRegistrationOptions($user));
        } catch (\Exception $e) {
           return $this->json(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/register/verify', methods: ['POST'])]
    public function registerVerify(Request $request, PasskeyAuthService $passkeyService): JsonResponse
    {
        $data       = json_decode($request->getContent(), true) ?? [];
        $email      = $data['email'] ?? null;
        $credential = $data['credential'] ?? null;

        if (!$email || !$credential) {
            return $this->json(['error' => 'Donnees invalides'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $passkeyService->verifyRegistration(json_encode($credential), $user);

            $jwt          = $this->jwtManager->create($user);
            $refreshToken = $this->refreshGenerator->createForUserWithTtl($user, 2592000);
            $this->refreshManager->save($refreshToken);

            return $this->json([
                'success'       => true,
                'token'         => $jwt,
                'refresh_token' => $refreshToken->getRefreshToken(),
                'user'          => ['id' => $user->getId(), 'email' => $user->getEmail()],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login/options', methods: ['POST'])]
    public function loginOptions(PasskeyAuthService $passkeyService): JsonResponse
    {
        try {
            return $this->json($passkeyService->getLoginOptions());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login/verify', methods: ['POST'])]
    public function loginVerify(Request $request, PasskeyAuthService $passkeyService): JsonResponse
    {
        $data       = json_decode($request->getContent(), true) ?? [];
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Credential requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user         = $passkeyService->verifyLogin(json_encode($credential));
            $jwt          = $this->jwtManager->create($user);
            $refreshToken = $this->refreshGenerator->createForUserWithTtl($user, 2592000);
            $this->refreshManager->save($refreshToken);

            return $this->json([
                'success'       => true,
                'token'         => $jwt,
                'refresh_token' => $refreshToken->getRefreshToken(),
                'user'          => ['id' => $user->getId(), 'email' => $user->getEmail()],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifie'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class RefreshTokenController extends AbstractController
{
    #[Route('/api/auth/refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        // la logique de refresh token
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refresh_token'])) {
            return $this->json(['error' => 'Refresh token requis'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $refreshToken = $data['refresh_token'];

        // Vérifier la validité du refresh token
        // Si valide, générer un nouveau access token
        // Sinon, retourner une erreur
        return $this->json(['message' => 'Refresh token endpoint - to be implemented'], JsonResponse::HTTP_NOT_IMPLEMENTED);

        // gesdinet/jwt-refresh-token-bundle
        
        
        

    }
}
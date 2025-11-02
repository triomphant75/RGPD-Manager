<?php

namespace App\Controller;

use App\Entity\DataBreachIncident;
use App\Service\DataBreachService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/incidents/security-breaches')]
class DataBreachController extends AbstractController
{
    public function __construct(
        private DataBreachService $breachService,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'breach_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DPO');
        $payload = json_decode($request->getContent(), true) ?? [];
        try {
            $incident = $this->breachService->reportIncident($payload);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->serialize($incident), Response::HTTP_CREATED);
    }

    #[Route('', name: 'breach_list', methods: ['GET'])]
    public function list(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DPO');
        $repo = $this->em->getRepository(DataBreachIncident::class);
        $items = array_map([$this, 'serialize'], $repo->findBy([], ['id' => 'DESC']));
        return new JsonResponse($items);
    }

    #[Route('/{id}', name: 'breach_get', methods: ['GET'])]
    public function getOne(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DPO');
        $incident = $this->em->getRepository(DataBreachIncident::class)->find($id);
        if (!$incident) return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        return new JsonResponse($this->serialize($incident));
    }

    #[Route('/{id}/notify-authority', name: 'breach_notify_authority', methods: ['POST'])]
    public function notifyAuthority(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DPO');
        $incident = $this->em->getRepository(DataBreachIncident::class)->find($id);
        if (!$incident) return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        $payload = json_decode($request->getContent(), true) ?? [];
        $ref = isset($payload['reference']) ? (string)$payload['reference'] : null;
        $this->breachService->markAuthorityNotified($incident, $ref);
        return new JsonResponse($this->serialize($incident));
    }

    #[Route('/{id}/notify-subjects', name: 'breach_notify_subjects', methods: ['POST'])]
    public function notifySubjects(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DPO');
        $incident = $this->em->getRepository(DataBreachIncident::class)->find($id);
        if (!$incident) return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        $this->breachService->markSubjectsNotified($incident);
        return new JsonResponse($this->serialize($incident));
    }

    private function serialize(DataBreachIncident $i): array
    {
        return [
            'id' => $i->getId(),
            'createdAt' => $i->getCreatedAt()->format(DATE_ATOM),
            'detectedAt' => $i->getDetectedAt()->format(DATE_ATOM),
            'severity' => $i->getSeverity(),
            'description' => $i->getDescription(),
            'personalDataInvolved' => $i->isPersonalDataInvolved(),
            'personalDataTypes' => $i->getPersonalDataTypes(),
            'affectedSubjectsCount' => $i->getAffectedSubjectsCount(),
            'riskAssessment' => $i->getRiskAssessment(),
            'notificationRequired' => $i->isNotificationRequired(),
            'dpoReviewed' => $i->isDpoReviewed(),
            'status' => $i->getStatus(),
            'authorityNotifiedAt' => $i->getAuthorityNotifiedAt()?->format(DATE_ATOM),
            'authorityReference' => $i->getAuthorityReference(),
            'subjectsNotifiedAt' => $i->getSubjectsNotifiedAt()?->format(DATE_ATOM),
            'containmentActions' => $i->getContainmentActions(),
            'remediationActions' => $i->getRemediationActions(),
            'source' => $i->getSource(),
        ];
    }
}

<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/api/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository
    ) {}

    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
public function index(): JsonResponse
{
    error_log('========== RÉCUPÉRATION DES NOTIFICATIONS ==========');
    
    $user = $this->getUser();
    error_log('🔑 Rôles: ' . json_encode($user->getRoles()));
    
    $notifications = $this->notificationRepository->findBy(
        ['user' => $user],
        ['createdAt' => 'DESC'],
        50
    );
    
    error_log('📬 Nombre de notifications trouvées: ' . count($notifications));
    
    foreach ($notifications as $notif) {
        error_log('  - ' . $notif->getType() . ': ' . $notif->getTitle() . ' (lu: ' . ($notif->isRead() ? 'oui' : 'non') . ')');
    }

    $data = array_map(function($notification) {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'treatment' => $notification->getTreatment() ? [
                'id' => $notification->getTreatment()->getId(),
                'nomTraitement' => $notification->getTreatment()->getNomTraitement(),
            ] : null,
            'data' => $notification->getData(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format('c'),
            'readAt' => $notification->getReadAt()?->format('c'),
        ];
    }, $notifications);

    error_log('📤 Envoi de ' . count($data) . ' notifications au frontend');
    error_log('====================================================');

    return $this->json($data);
}

    #[Route('/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $user = $this->getUser();
        $count = $this->notificationRepository->count([
            'user' => $user,
            'isRead' => false
        ]);

        return $this->json(['count' => $count]);
    }

    #[Route('/{id}/mark-as-read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);

        if (!$notification) {
            return $this->json(['error' => 'Notification non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $notification->setIsRead(true);
        $notification->setReadAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json(['message' => 'Notification marquée comme lue']);
    }

    #[Route('/mark-all-as-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findBy([
            'user' => $user,
            'isRead' => false
        ]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
            $notification->setReadAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Toutes les notifications marquées comme lues']);
    }
}
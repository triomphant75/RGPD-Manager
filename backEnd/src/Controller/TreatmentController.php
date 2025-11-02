<?php

namespace App\Controller;

use App\Entity\Treatment;
use App\Repository\TreatmentRepository;
use App\Service\AuditLogger;
use App\Service\NotificationService;
use App\Service\TreatmentValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/treatments')]
#[IsGranted('ROLE_USER')]
class TreatmentController extends AbstractController
{
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TreatmentRepository $treatmentRepository,
        private NotificationService $notificationService,
        private TreatmentValidator $validator
    ) {}

    #[Route('', name: 'api_treatments_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $treatments = $this->treatmentRepository->findAll();
        
        $data = array_map(function($treatment) {
            return $this->formatTreatmentResponse($treatment);
        }, $treatments);

        return $this->json($data);
    }

    #[Route('/pending-validation', name: 'api_treatments_pending', methods: ['GET'])]
    public function getPendingValidation(): JsonResponse
    {
        error_log('========== RÉCUPÉRATION TRAITEMENTS EN ATTENTE ==========');
        
        $user = $this->getUser();
        if (!$user) {
            error_log('❌ Utilisateur non connecté');
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        
        error_log('🔑 Rôles: ' . json_encode($user->getRoles()));
        
        if (!in_array('ROLE_DPO', $user->getRoles(), true)) {
            error_log('❌ Utilisateur n\'a pas le rôle ROLE_DPO');
            return $this->json(['error' => 'Accès refusé - ROLE_DPO requis'], Response::HTTP_FORBIDDEN);
        }
        
        try {
            error_log('🔍 Recherche des traitements "En validation"...');
            
            $treatments = $this->treatmentRepository->findBy(['etatTraitement' => 'En validation']);
            
            error_log('📊 Nombre de traitements trouvés: ' . count($treatments));
            
            $data = [];
            foreach ($treatments as $treatment) {
                error_log('  - Traitement ID: ' . $treatment->getId());
                try {
                    $data[] = $this->formatTreatmentResponse($treatment);
                } catch (\Exception $formatError) {
                    error_log('❌ Erreur formatage traitement ' . $treatment->getId() . ': ' . $formatError->getMessage());
                }
            }

            error_log('✅ Envoi de ' . count($data) . ' traitements au frontend');
            error_log('========================================================');

            return $this->json($data);
            
        } catch (\Exception $e) {
            error_log('❌ ERREUR dans getPendingValidation: ' . $e->getMessage());
            error_log('📍 Fichier: ' . $e->getFile() . ' Ligne: ' . $e->getLine());
            
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_treatments_show', methods: ['GET'])]
    public function show(int $id, AuditLogger $auditLogger): JsonResponse
    {
        $treatment = $this->treatmentRepository->find($id);

        if (!$treatment) {
            return $this->json(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Log de l'accès au traitement
          $auditLogger->logDataAccess(
        'VIEW',
        'Treatment',
        $id,
        ['treatment_name' => $treatment->getNomTraitement()]
        );

        return $this->json($this->formatTreatmentResponse($treatment));
    }
    
    // Création d'un traitement
    #[Route('', name: 'api_treatments_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        error_log('========== CRÉATION DE TRAITEMENT ==========');
        $data = json_decode($request->getContent(), true);
        error_log('📦 Données reçues: ' . json_encode($data, JSON_PRETTY_PRINT));
        
        // Validation des données
        $validationErrors = $this->validator->validateTreatmentData($data, false);
        if (!empty($validationErrors)) {
            error_log('❌ Erreurs de validation: ' . json_encode($validationErrors));
            return $this->json(
                ['errors' => $validationErrors],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier l'unicité du nom de traitement
        $existingTreatment = $this->treatmentRepository->findOneBy([
            'nomTraitement' => $data['nomTraitement']
        ]);

        if ($existingTreatment) {
            error_log("❌ Nom de traitement déjà existant: {$data['nomTraitement']}");
            return $this->json(
                [
                    'error' => "Un traitement avec le nom \"{$data['nomTraitement']}\" existe déjà. Veuillez choisir un nom différent.",
                    'field' => 'nomTraitement'
                ],
                Response::HTTP_CONFLICT // 409 Conflict
            );
        }

        // Création et persistance de l'entité
        try {
            error_log('🔨 Création de l\'entité Treatment...');
            $treatment = new Treatment();
            $this->populateTreatment($treatment, $data);
            $treatment->setCreatedBy($this->getUser());
            $treatment->setEtatTraitement($data['etatTraitement'] ?? 'Brouillon');

            error_log('💾 Persistance dans la base de données...');
            $this->entityManager->persist($treatment);
            $this->entityManager->flush();

            error_log('✅ Traitement créé avec succès - ID: ' . $treatment->getId());
            error_log('==========================================');

            return $this->json(
                $this->formatTreatmentResponse($treatment),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            error_log('❌ ERREUR lors de la création: ' . $e->getMessage());
            error_log('📍 Trace: ' . $e->getTraceAsString());
            error_log('==========================================');
            
            return $this->json(
                ['error' => 'Erreur lors de la création : ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'api_treatments_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        error_log('========== MISE À JOUR DE TRAITEMENT ==========');
        error_log("📝 ID du traitement: {$id}");
        
        $treatment = $this->treatmentRepository->find($id);

        if (!$treatment) {
            error_log("❌ Traitement ID {$id} non trouvé");
            return $this->json(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($treatment->getEtatTraitement() === 'Archivé') {
            error_log("❌ Tentative de modification d'un traitement archivé");
            return $this->json(
                ['error' => 'Les traitements archivés ne peuvent pas être modifiés'],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $data = json_decode($request->getContent(), true);
            error_log('📦 Données de mise à jour: ' . json_encode($data, JSON_PRETTY_PRINT));
            
            // Validation des données (mise à jour = pas tous les champs obligatoires)
            $validationErrors = $this->validator->validateTreatmentData($data, true);
            if (!empty($validationErrors)) {
                error_log('❌ Erreurs de validation: ' . json_encode($validationErrors));
                return $this->json(
                    ['errors' => $validationErrors],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            // Vérifier l'unicité du nom de traitement si modifié
            if (isset($data['nomTraitement']) && $data['nomTraitement'] !== $treatment->getNomTraitement()) {
                $existingTreatment = $this->treatmentRepository->findOneBy([
                    'nomTraitement' => $data['nomTraitement']
                ]);

                if ($existingTreatment) {
                    error_log("❌ Nom de traitement déjà existant: {$data['nomTraitement']}");
                    return $this->json(
                        [
                            'error' => "Un traitement avec le nom \"{$data['nomTraitement']}\" existe déjà. Veuillez choisir un nom différent.",
                            'field' => 'nomTraitement'
                        ],
                        Response::HTTP_CONFLICT // 409 Conflict
                    );
                }
            }

            $this->populateTreatment($treatment, $data);
            $treatment->setUpdatedAt(new \DateTimeImmutable());

            error_log('💾 Sauvegarde des modifications...');
            $this->entityManager->flush();

            error_log('✅ Traitement mis à jour avec succès');
            error_log('===============================================');

            return $this->json($this->formatTreatmentResponse($treatment));
        } catch (\Exception $e) {
            error_log('❌ ERREUR lors de la mise à jour: ' . $e->getMessage());
            error_log('📍 Trace: ' . $e->getTraceAsString());
            error_log('===============================================');
            
            return $this->json(
                ['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}/submit', name: 'api_treatments_submit', methods: ['POST'])]
    public function submitForValidation(int $id): JsonResponse
    {
        error_log('========== SOUMISSION AU DPO ==========');
        error_log('🔍 ID reçu: ' . $id);
        
        try {
            $treatment = $this->treatmentRepository->find($id);

            if (!$treatment) {
                error_log('❌ Traitement non trouvé');
                return $this->json(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
            }

            error_log('✅ Traitement trouvé: ' . $treatment->getNomTraitement());
            error_log('📋 État actuel: ' . $treatment->getEtatTraitement());

            // ✅ MODIFICATION : Retrait de "Refusé" - seuls Brouillon et A modifier peuvent être soumis
            if ($treatment->getEtatTraitement() !== 'Brouillon' && 
                $treatment->getEtatTraitement() !== 'A modifier') {
                error_log('❌ État invalide pour soumission: ' . $treatment->getEtatTraitement());
                return $this->json(
                    ['error' => 'Ce traitement ne peut pas être soumis à validation'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            error_log('🔄 Changement d\'état vers "En validation"...');
            $treatment->setEtatTraitement('En validation');
            $treatment->setUpdatedAt(new \DateTimeImmutable());
            
            error_log('💾 Flush du traitement...');
            $this->entityManager->flush();
            error_log('✅ État changé en "En validation"');

            error_log('🔔 Appel du NotificationService->notifyTreatmentSubmitted()...');
            
            try {
                $this->notificationService->notifyTreatmentSubmitted($treatment);
                error_log('✅ NotificationService terminé avec succès');
            } catch (\Exception $notifError) {
                error_log('❌ ERREUR dans NotificationService: ' . $notifError->getMessage());
                error_log('📍 Fichier: ' . $notifError->getFile() . ' Ligne: ' . $notifError->getLine());
                error_log('⚠️ Traitement en validation malgré l\'erreur de notification');
            }

            error_log('=======================================');

            return $this->json([
                'message' => 'Traitement soumis à validation',
                'treatment' => $this->formatTreatmentResponse($treatment)
            ]);
            
        } catch (\Exception $e) {
            error_log('❌ ERREUR CRITIQUE GLOBALE: ' . $e->getMessage());
            error_log('📍 Fichier: ' . $e->getFile() . ' Ligne: ' . $e->getLine());
            
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/validate', name: 'api_treatments_validate', methods: ['POST'])]
    #[IsGranted('ROLE_DPO')]
    public function validate(int $id): JsonResponse
    {
        $treatment = $this->treatmentRepository->find($id);

        if (!$treatment) {
            return $this->json(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $treatment->setEtatTraitement('Validé');
        $treatment->setDateValidation(new \DateTimeImmutable());
        $treatment->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->notificationService->notifyTreatmentValidated($treatment);

        return $this->json([
            'message' => 'Traitement validé avec succès',
            'treatment' => $this->formatTreatmentResponse($treatment)
        ]);
    }

    // ❌ SUPPRIMÉ : La route /reject est complètement retirée

    #[Route('/{id}/request-modification', name: 'api_treatments_request_modification', methods: ['POST'])]
    #[IsGranted('ROLE_DPO')]
    public function requestModification(int $id, Request $request): JsonResponse
    {
        $treatment = $this->treatmentRepository->find($id);

        if (!$treatment) {
            return $this->json(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        // Validation du commentaire
        $validationErrors = $this->validator->validateModificationRequest($data);
        if (!empty($validationErrors)) {
            return $this->json(
                ['errors' => $validationErrors],
                Response::HTTP_BAD_REQUEST
            );
        }
        
        $comment = $data['comment'];

        $treatment->setEtatTraitement('A modifier');
        $treatment->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->notificationService->notifyTreatmentToModify($treatment, $comment);

        return $this->json([
            'message' => 'Demande de modification envoyée',
            'treatment' => $this->formatTreatmentResponse($treatment)
        ]);
    }


    #[Route('/{id}/archive', name: 'api_treatments_archive', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function archive(int $id): JsonResponse
    {
        error_log('========== ARCHIVAGE DE TRAITEMENT ==========');
        error_log('📦 ID du traitement: ' . $id);
        
        $treatment = $this->treatmentRepository->find($id);

        if (!$treatment) {
            error_log('❌ Traitement non trouvé');
            return $this->json(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        error_log('📋 État actuel: ' . $treatment->getEtatTraitement());

        // Seuls les traitements "Validé" peuvent être archivés
        if ($treatment->getEtatTraitement() !== 'Validé') {
            error_log('❌ État invalide pour archivage: ' . $treatment->getEtatTraitement());
            return $this->json(
                ['error' => 'Seuls les traitements validés peuvent ��tre archivés'],
                Response::HTTP_BAD_REQUEST
            );
        }

        error_log('🔄 Archivage du traitement...');
        $treatment->setEtatTraitement('Archivé');
        $treatment->setDateArchivage(new \DateTimeImmutable());
        $treatment->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();

        error_log('��� Traitement archivé avec succès');
        error_log('📅 Date d\'archivage: ' . $treatment->getDateArchivage()->format('Y-m-d H:i:s'));
        error_log('============================================');

        return $this->json([
            'message' => 'Traitement archivé avec succès',
            'treatment' => $this->formatTreatmentResponse($treatment)
        ]);
    }


    #[Route('/{id}', name: 'api_treatments_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $treatment = $this->treatmentRepository->find($id);

        if (!$treatment) {
            return $this->json(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($treatment->getEtatTraitement() !== 'Brouillon') {
            return $this->json(
                ['error' => 'Seuls les traitements en brouillon peuvent être supprimés'],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->entityManager->remove($treatment);
        $this->entityManager->flush();

        return $this->json(['message' => 'Traitement supprimé avec succès']);
    }

    private function populateTreatment(Treatment $treatment, array $data): void
    {
        if (isset($data['responsableTraitement'])) {
            $treatment->setResponsableTraitement($data['responsableTraitement']);
        }
        if (isset($data['adressePostale'])) {
            $treatment->setAdressePostale($data['adressePostale']);
        }
        if (isset($data['telephone'])) {
            $treatment->setTelephone($data['telephone']);
        }
        if (isset($data['referentRGPD'])) {
            $treatment->setReferentRGPD($data['referentRGPD']);
        }
        if (isset($data['service'])) {
            $treatment->setService($data['service']);
        }
        if (isset($data['nomTraitement'])) {
            $treatment->setNomTraitement($data['nomTraitement']);
        }
        if (isset($data['numeroReference'])) {
            $treatment->setNumeroReference($data['numeroReference']);
        }
        if (isset($data['derniereMiseAJour'])) {
            $treatment->setDerniereMiseAJour(new \DateTime($data['derniereMiseAJour']));
        }
        if (isset($data['finalite'])) {
            $treatment->setFinalite($data['finalite']);
        }
        if (isset($data['baseJuridique'])) {
            $treatment->setBaseJuridique($data['baseJuridique']);
        }
        if (isset($data['categoriePersonnes'])) {
            $treatment->setCategoriePersonnes(is_array($data['categoriePersonnes']) ? $data['categoriePersonnes'] : []);
        }
        if (isset($data['volumePersonnes'])) {
            $treatment->setVolumePersonnes($data['volumePersonnes']);
        }
        if (isset($data['sourceDonnees'])) {
            $treatment->setSourceDonnees(is_array($data['sourceDonnees']) ? $data['sourceDonnees'] : []);
        }
        if (isset($data['donneesPersonnelles'])) {
            $treatment->setDonneesPersonnelles(is_array($data['donneesPersonnelles']) ? $data['donneesPersonnelles'] : []);
        }
        if (isset($data['donneesHautementPersonnelles'])) {
            $treatment->setDonneesHautementPersonnelles(is_array($data['donneesHautementPersonnelles']) ? $data['donneesHautementPersonnelles'] : []);
        }
        if (isset($data['personnesVulnerables'])) {
            $treatment->setPersonnesVulnerables((bool)$data['personnesVulnerables']);
        }
        if (isset($data['donneesPapier'])) {
            $treatment->setDonneesPapier((bool)$data['donneesPapier']);
        }
        if (isset($data['referentOperationnel'])) {
            $treatment->setReferentOperationnel($data['referentOperationnel']);
        }
        if (isset($data['destinatairesInternes'])) {
            $treatment->setDestinatairesInternes(is_array($data['destinatairesInternes']) ? $data['destinatairesInternes'] : []);
        }
        if (isset($data['destinatairesExternes'])) {
            $treatment->setDestinatairesExternes(is_array($data['destinatairesExternes']) ? $data['destinatairesExternes'] : []);
        }
        if (isset($data['sousTraitant'])) {
            $treatment->setSousTraitant($data['sousTraitant']);
        }
        if (isset($data['outilInformatique'])) {
            $treatment->setOutilInformatique($data['outilInformatique']);
        }
        if (isset($data['administrateurLogiciel'])) {
            $treatment->setAdministrateurLogiciel($data['administrateurLogiciel']);
        }
        if (isset($data['hebergement'])) {
            $treatment->setHebergement($data['hebergement']);
        }
        if (isset($data['transfertHorsUE'])) {
            $treatment->setTransfertHorsUE((bool)$data['transfertHorsUE']);
        }
        if (isset($data['dureeBaseActive'])) {
            $treatment->setDureeBaseActive($data['dureeBaseActive']);
        }
        if (isset($data['dureeBaseIntermediaire'])) {
            $treatment->setDureeBaseIntermediaire($data['dureeBaseIntermediaire']);
        }
        if (isset($data['texteReglementaire'])) {
            $treatment->setTexteReglementaire($data['texteReglementaire']);
        }
        if (isset($data['archivage'])) {
            $treatment->setArchivage((bool)$data['archivage']);
        }
        if (isset($data['securitePhysique'])) {
            $treatment->setSecuritePhysique(is_array($data['securitePhysique']) ? $data['securitePhysique'] : []);
        }
        if (isset($data['controleAccesLogique'])) {
            $treatment->setControleAccesLogique(is_array($data['controleAccesLogique']) ? $data['controleAccesLogique'] : []);
        }
        if (isset($data['tracabilite'])) {
            $treatment->setTracabilite((bool)$data['tracabilite']);
        }
        if (isset($data['sauvegardesDonnees'])) {
            $treatment->setSauvegardesDonnees((bool)$data['sauvegardesDonnees']);
        }
        if (isset($data['chiffrementAnonymisation'])) {
            $treatment->setChiffrementAnonymisation((bool)$data['chiffrementAnonymisation']);
        }
        if (isset($data['mecanismePurge'])) {
            $treatment->setMecanismePurge($data['mecanismePurge']);
        }
        if (isset($data['droitsPersonnes'])) {
            $treatment->setDroitsPersonnes($data['droitsPersonnes']);
        }
        if (isset($data['documentMention'])) {
            $treatment->setDocumentMention($data['documentMention']);
        }
        if (isset($data['etatTraitement'])) {
            $treatment->setEtatTraitement($data['etatTraitement']);
        }
    }

    private function formatTreatmentResponse(Treatment $treatment): array
    {
        return [
            'id' => $treatment->getId(),
            'responsableTraitement' => $treatment->getResponsableTraitement(),
            'adressePostale' => $treatment->getAdressePostale(),
            'telephone' => $treatment->getTelephone(),
            'referentRGPD' => $treatment->getReferentRGPD(),
            'service' => $treatment->getService(),
            'nomTraitement' => $treatment->getNomTraitement(),
            'numeroReference' => $treatment->getNumeroReference(),
            'derniereMiseAJour' => $treatment->getDerniereMiseAJour()->format('Y-m-d'),
            'finalite' => $treatment->getFinalite(),
            'baseJuridique' => $treatment->getBaseJuridique(),
            'categoriePersonnes' => $treatment->getCategoriePersonnes() ?? [],
            'volumePersonnes' => $treatment->getVolumePersonnes(),
            'sourceDonnees' => $treatment->getSourceDonnees() ?? [],
            'donneesPersonnelles' => $treatment->getDonneesPersonnelles() ?? [],
            'donneesHautementPersonnelles' => $treatment->getDonneesHautementPersonnelles() ?? [],
            'personnesVulnerables' => $treatment->isPersonnesVulnerables(),
            'donneesPapier' => $treatment->isDonneesPapier(),
            'referentOperationnel' => $treatment->getReferentOperationnel(),
            'destinatairesInternes' => $treatment->getDestinatairesInternes() ?? [],
            'destinatairesExternes' => $treatment->getDestinatairesExternes() ?? [],
            'sousTraitant' => $treatment->getSousTraitant(),
            'outilInformatique' => $treatment->getOutilInformatique(),
            'administrateurLogiciel' => $treatment->getAdministrateurLogiciel(),
            'hebergement' => $treatment->getHebergement(),
            'transfertHorsUE' => $treatment->isTransfertHorsUE(),
            'dureeBaseActive' => $treatment->getDureeBaseActive(),
            'dureeBaseIntermediaire' => $treatment->getDureeBaseIntermediaire(),
            'texteReglementaire' => $treatment->getTexteReglementaire(),
            'archivage' => $treatment->isArchivage(),
            'securitePhysique' => $treatment->getSecuritePhysique() ?? [],
            'controleAccesLogique' => $treatment->getControleAccesLogique() ?? [],
            'tracabilite' => $treatment->isTracabilite(),
            'sauvegardesDonnees' => $treatment->isSauvegardesDonnees(),
            'chiffrementAnonymisation' => $treatment->isChiffrementAnonymisation(),
            'mecanismePurge' => $treatment->getMecanismePurge(),
            'droitsPersonnes' => $treatment->getDroitsPersonnes(),
            'documentMention' => $treatment->getDocumentMention(),
            'etatTraitement' => $treatment->getEtatTraitement(),
            'raisonRefus' => $treatment->getRaisonRefus(),
            'dateValidation' => $treatment->getDateValidation()?->format('c'),
            'createdBy' => $treatment->getCreatedBy()->getEmail(),
            'createdAt' => $treatment->getCreatedAt()->format('c'),
            'updatedAt' => $treatment->getUpdatedAt()->format('c'),
            'dateArchivage' => $treatment->getDateArchivage()?->format('c')
        ];
    }
}

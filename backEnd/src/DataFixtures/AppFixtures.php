<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Treatment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ========== UTILISATEURS ==========
        
        // Administrateur
        $admin = new User();
        $admin->setEmail('admin@rgpd.com');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // DPO (Délégué à la Protection des Données)
        $dpo = new User();
        $dpo->setEmail('dpo@rgpd.com');
        $dpo->setRoles(['ROLE_USER', 'ROLE_DPO']);
        $dpo->setPassword($this->passwordHasher->hashPassword($dpo, 'dpo123'));
        $manager->persist($dpo);

        // Utilisateur standard
        $user = new User();
        $user->setEmail('user@rgpd.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $manager->persist($user);

        // ========== TRAITEMENTS ==========

        // Traitement 1 - RH - Validé
        $treatment1 = new Treatment();
        $treatment1->setResponsableTraitement('Jean Dupont')
            ->setAdressePostale('123 Rue de la République, 75001 Paris')
            ->setTelephone('01 23 45 67 89')
            ->setReferentRGPD('Marie Martin - DPO')
            ->setService('Ressources humaines')
            ->setNomTraitement('Gestion des dossiers salariés')
            ->setNumeroReference('RH-001')
            ->setDerniereMiseAJour(new \DateTime())
            ->setFinalite('Gérer les informations administratives et contractuelles des salariés')
            ->setBaseJuridique('Contrat')
            ->setCategoriePersonnes(['Salariés'])
            ->setVolumePersonnes('50-100')
            ->setSourceDonnees(['Formulaires internes', 'Candidatures'])
            ->setDonneesPersonnelles(['État civil', 'Vie professionnelle', 'Économique'])
            ->setDonneesHautementPersonnelles([])
            ->setPersonnesVulnerables(false)
            ->setDonneesPapier(true)
            ->setReferentOperationnel('Sophie Bernard - RH')
            ->setDestinatairesInternes(['Direction', 'Service RH', 'Service Comptabilité'])
            ->setDestinatairesExternes(['Expert-comptable', 'Organisme de prévoyance'])
            ->setSousTraitant('ADP Payroll Services')
            ->setOutilInformatique('SIRH Lucca')
            ->setAdministrateurLogiciel('Service IT')
            ->setHebergement('Cloud FR')
            ->setTransfertHorsUE(false)
            ->setDureeBaseActive('Durée du contrat + 5 ans')
            ->setDureeBaseIntermediaire('10 ans après fin du contrat')
            ->setTexteReglementaire('Code du travail - Articles L1234-19 et suivants')
            ->setArchivage(true)
            ->setSecuritePhysique(['Locaux fermés à clé', 'Armoires sécurisées'])
            ->setControleAccesLogique(['Authentification forte', 'Gestion des habilitations'])
            ->setTracabilite(true)
            ->setSauvegardesDonnees(true)
            ->setChiffrementAnonymisation(true)
            ->setMecanismePurge('Suppression automatique après expiration des délais légaux')
            ->setDroitsPersonnes('Droit d\'accès, de rectification, d\'opposition, de limitation du traitement')
            ->setDocumentMention('Contrat de travail - Clause de confidentialité')
            ->setEtatTraitement('Validé')
            ->setDateValidation(new \DateTimeImmutable('-5 days'))
            ->setCreatedBy($admin);
        $manager->persist($treatment1);

        // Traitement 2 - Communication - En validation
        $treatment2 = new Treatment();
        $treatment2->setResponsableTraitement('Jean Dupont')
            ->setAdressePostale('123 Rue de la République, 75001 Paris')
            ->setTelephone('01 23 45 67 89')
            ->setReferentRGPD('Marie Martin - DPO')
            ->setService('Communication')
            ->setNomTraitement('Gestion de la newsletter')
            ->setNumeroReference('COM-001')
            ->setDerniereMiseAJour(new \DateTime())
            ->setFinalite('Envoyer des informations commerciales et actualités de l\'entreprise')
            ->setBaseJuridique('Consentement')
            ->setCategoriePersonnes(['Adhérents', 'Donateurs'])
            ->setVolumePersonnes('>1000')
            ->setSourceDonnees(['Formulaire d\'inscription en ligne'])
            ->setDonneesPersonnelles(['État civil', 'Connexion/logs'])
            ->setDonneesHautementPersonnelles([])
            ->setPersonnesVulnerables(false)
            ->setDonneesPapier(false)
            ->setReferentOperationnel('Claire Dubois - Responsable Communication')
            ->setDestinatairesInternes(['Service Communication'])
            ->setDestinatairesExternes(['Prestataire emailing'])
            ->setSousTraitant('Mailchimp')
            ->setOutilInformatique('Mailchimp')
            ->setAdministrateurLogiciel('Service Communication')
            ->setHebergement('Cloud hors UE')
            ->setTransfertHorsUE(true)
            ->setDureeBaseActive('3 ans à compter de la dernière interaction')
            ->setDureeBaseIntermediaire(null)
            ->setTexteReglementaire('RGPD - Article 6.1.a')
            ->setArchivage(false)
            ->setSecuritePhysique([])
            ->setControleAccesLogique(['Authentification', 'Accès limité au service'])
            ->setTracabilite(true)
            ->setSauvegardesDonnees(true)
            ->setChiffrementAnonymisation(true)
            ->setMecanismePurge('Suppression automatique après 3 ans d\'inactivité')
            ->setDroitsPersonnes('Droit de se désabonner à tout moment, d\'accès, de rectification et de suppression')
            ->setDocumentMention('Formulaire d\'inscription - Consentement explicite')
            ->setEtatTraitement('En validation')
            ->setCreatedBy($user);
        $manager->persist($treatment2);

        // Traitement 3 - Comptabilité - Brouillon
        $treatment3 = new Treatment();
        $treatment3->setResponsableTraitement('Jean Dupont')
            ->setAdressePostale('123 Rue de la République, 75001 Paris')
            ->setTelephone('01 23 45 67 89')
            ->setReferentRGPD('Marie Martin - DPO')
            ->setService('Comptabilité')
            ->setNomTraitement('Gestion de la facturation')
            ->setNumeroReference('COMPTA-001')
            ->setDerniereMiseAJour(new \DateTime('-2 months'))
            ->setFinalite('Gérer la facturation clients et fournisseurs')
            ->setBaseJuridique('Obligation légale')
            ->setCategoriePersonnes(['Clients', 'Fournisseurs'])
            ->setVolumePersonnes('100-1000')
            ->setSourceDonnees(['Bons de commande', 'Contrats commerciaux'])
            ->setDonneesPersonnelles(['État civil', 'Économique'])
            ->setDonneesHautementPersonnelles([])
            ->setPersonnesVulnerables(false)
            ->setDonneesPapier(true)
            ->setReferentOperationnel('Pierre Leroy - Responsable Comptabilité')
            ->setDestinatairesInternes(['Service Comptabilité', 'Direction'])
            ->setDestinatairesExternes(['Expert-comptable', 'Administration fiscale'])
            ->setSousTraitant('Cabinet Comptable Associés')
            ->setOutilInformatique('Sage Comptabilité')
            ->setAdministrateurLogiciel('Service IT')
            ->setHebergement('Serveur interne')
            ->setTransfertHorsUE(false)
            ->setDureeBaseActive('10 ans à compter de la clôture de l\'exercice')
            ->setDureeBaseIntermediaire(null)
            ->setTexteReglementaire('Code de commerce - Article L123-22')
            ->setArchivage(true)
            ->setSecuritePhysique(['Salle serveur sécurisée', 'Contrôle d\'accès biométrique'])
            ->setControleAccesLogique(['VPN', 'Authentification forte', 'Gestion des droits'])
            ->setTracabilite(true)
            ->setSauvegardesDonnees(true)
            ->setChiffrementAnonymisation(true)
            ->setMecanismePurge('Archivage légal puis destruction après délai légal')
            ->setDroitsPersonnes('Droit d\'accès et de rectification (limité par obligations légales)')
            ->setDocumentMention('Conditions générales de vente')
            ->setEtatTraitement('Brouillon')
            ->setCreatedBy($user);
        $manager->persist($treatment3);

        // Traitement 4 - Informatique - Refusé
        $treatment4 = new Treatment();
        $treatment4->setResponsableTraitement('Jean Dupont')
            ->setAdressePostale('123 Rue de la République, 75001 Paris')
            ->setTelephone('01 23 45 67 89')
            ->setReferentRGPD('Marie Martin - DPO')
            ->setService('Informatique')
            ->setNomTraitement('Logs des connexions système')
            ->setNumeroReference('IT-001')
            ->setDerniereMiseAJour(new \DateTime('-1 week'))
            ->setFinalite('Tracer les connexions pour la sécurité du système')
            ->setBaseJuridique('Intérêt légitime')
            ->setCategoriePersonnes(['Salariés', 'Prestataires'])
            ->setVolumePersonnes('50-100')
            ->setSourceDonnees(['Systèmes d\'authentification'])
            ->setDonneesPersonnelles(['État civil', 'Connexion/logs'])
            ->setDonneesHautementPersonnelles([])
            ->setPersonnesVulnerables(false)
            ->setDonneesPapier(false)
            ->setReferentOperationnel('Thomas Durand - DSI')
            ->setDestinatairesInternes(['Service IT', 'Direction'])
            ->setDestinatairesExternes([])
            ->setSousTraitant(null)
            ->setOutilInformatique('Système de logs interne')
            ->setAdministrateurLogiciel('Service IT')
            ->setHebergement('Serveur interne')
            ->setTransfertHorsUE(false)
            ->setDureeBaseActive('1 an')
            ->setDureeBaseIntermediaire(null)
            ->setTexteReglementaire('RGPD - Article 6.1.f')
            ->setArchivage(false)
            ->setSecuritePhysique(['Salle serveur sécurisée'])
            ->setControleAccesLogique(['Authentification forte', 'Chiffrement'])
            ->setTracabilite(true)
            ->setSauvegardesDonnees(true)
            ->setChiffrementAnonymisation(true)
            ->setMecanismePurge('Suppression automatique après 1 an')
            ->setDroitsPersonnes('Droit d\'accès et de rectification')
            ->setDocumentMention('Charte informatique')
            ->setEtatTraitement('Refusé')
            ->setRaisonRefus('La durée de conservation doit être précisée et la base juridique mieux justifiée')
            ->setCreatedBy($user);
        $manager->persist($treatment4);

        // Traitement 5 - Direction - A modifier
        $treatment5 = new Treatment();
        $treatment5->setResponsableTraitement('Jean Dupont')
            ->setAdressePostale('123 Rue de la République, 75001 Paris')
            ->setTelephone('01 23 45 67 89')
            ->setReferentRGPD('Marie Martin - DPO')
            ->setService('Direction')
            ->setNomTraitement('Gestion des candidatures')
            ->setNumeroReference('DIR-001')
            ->setDerniereMiseAJour(new \DateTime('-3 days'))
            ->setFinalite('Gérer les candidatures reçues pour les recrutements')
            ->setBaseJuridique('Consentement')
            ->setCategoriePersonnes(['Candidats'])
            ->setVolumePersonnes('<50')
            ->setSourceDonnees(['CV', 'Lettres de motivation', 'Formulaires en ligne'])
            ->setDonneesPersonnelles(['État civil', 'Vie professionnelle'])
            ->setDonneesHautementPersonnelles([])
            ->setPersonnesVulnerables(false)
            ->setDonneesPapier(true)
            ->setReferentOperationnel('Sophie Bernard - RH')
            ->setDestinatairesInternes(['Service RH', 'Direction'])
            ->setDestinatairesExternes([])
            ->setSousTraitant(null)
            ->setOutilInformatique('Application de recrutement')
            ->setAdministrateurLogiciel('Service IT')
            ->setHebergement('Cloud FR')
            ->setTransfertHorsUE(false)
            ->setDureeBaseActive('2 ans après le processus de recrutement')
            ->setDureeBaseIntermediaire(null)
            ->setTexteReglementaire('RGPD - Article 6.1.a')
            ->setArchivage(false)
            ->setSecuritePhysique(['Armoires fermées à clé'])
            ->setControleAccesLogique(['Authentification'])
            ->setTracabilite(false)
            ->setSauvegardesDonnees(true)
            ->setChiffrementAnonymisation(false)
            ->setMecanismePurge('Suppression manuelle après 2 ans')
            ->setDroitsPersonnes('Droit d\'accès, de rectification et de suppression')
            ->setDocumentMention('Formulaire de candidature')
            ->setEtatTraitement('A modifier')
            ->setCreatedBy($user);
        $manager->persist($treatment5);

        // Traitement 6 - Logistique - Archivé
        $treatment6 = new Treatment();
        $treatment6->setResponsableTraitement('Jean Dupont')
            ->setAdressePostale('123 Rue de la République, 75001 Paris')
            ->setTelephone('01 23 45 67 89')
            ->setReferentRGPD('Marie Martin - DPO')
            ->setService('Logistique')
            ->setNomTraitement('Suivi des livraisons 2020')
            ->setNumeroReference('LOG-001')
            ->setDerniereMiseAJour(new \DateTime('-2 years'))
            ->setFinalite('Gérer les livraisons et suivis clients')
            ->setBaseJuridique('Contrat')
            ->setCategoriePersonnes(['Clients'])
            ->setVolumePersonnes('>1000')
            ->setSourceDonnees(['Bons de commande'])
            ->setDonneesPersonnelles(['État civil', 'Économique'])
            ->setDonneesHautementPersonnelles([])
            ->setPersonnesVulnerables(false)
            ->setDonneesPapier(false)
            ->setReferentOperationnel('Marc Petit - Responsable Logistique')
            ->setDestinatairesInternes(['Service Logistique'])
            ->setDestinatairesExternes(['Transporteurs'])
            ->setSousTraitant('DHL Express')
            ->setOutilInformatique('Système de gestion logistique')
            ->setAdministrateurLogiciel('Service IT')
            ->setHebergement('Cloud UE')
            ->setTransfertHorsUE(false)
            ->setDureeBaseActive('3 ans après livraison')
            ->setDureeBaseIntermediaire('5 ans')
            ->setTexteReglementaire('Code de commerce')
            ->setArchivage(true)
            ->setSecuritePhysique([])
            ->setControleAccesLogique(['Authentification'])
            ->setTracabilite(true)
            ->setSauvegardesDonnees(true)
            ->setChiffrementAnonymisation(true)
            ->setMecanismePurge('Archivage automatique puis suppression')
            ->setDroitsPersonnes('Droit d\'accès et de rectification')
            ->setDocumentMention('Conditions générales de vente')
            ->setEtatTraitement('Archivé')
            ->setDateArchivage(new \DateTimeImmutable('-1 year'))
            ->setCreatedBy($admin);
        $manager->persist($treatment6);

        $manager->flush();
    }
}
<?php

namespace App\Entity;

use App\Repository\TreatmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: TreatmentRepository::class)]
#[ORM\Table(name: 'treatments')]
class Treatment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // BLOC IDENTIFICATION
    #[ORM\Column(length: 255)]
    private ?string $responsableTraitement = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $adressePostale = null;

    #[ORM\Column(length: 50)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $referentRGPD = null;

    #[ORM\Column(length: 100)]
    private ?string $service = null;

    #[ORM\Column(length: 255)]
    private ?string $nomTraitement = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroReference = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $derniereMiseAJour = null;

    // BLOC DESCRIPTION
    #[ORM\Column(type: Types::TEXT)]
    private ?string $finalite = null;

    #[ORM\Column(length: 100)]
    private ?string $baseJuridique = null;

    #[ORM\Column(type: Types::JSON)]
    private array $categoriePersonnes = [];

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $volumePersonnes = null;

    #[ORM\Column(type: Types::JSON)]
    private array $sourceDonnees = [];

    #[ORM\Column(type: Types::JSON)]
    private array $donneesPersonnelles = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $donneesHautementPersonnelles = [];

    #[ORM\Column]
    private ?bool $personnesVulnerables = false;

    #[ORM\Column]
    private ?bool $donneesPapier = false;

    // BLOC ACTEURS ET ACCÈS
    #[ORM\Column(length: 255)]
    private ?string $referentOperationnel = null;

    #[ORM\Column(type: Types::JSON)]
    private array $destinatairesInternes = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $destinatairesExternes = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sousTraitant = null;

    #[ORM\Column(length: 255)]
    private ?string $outilInformatique = null;

    #[ORM\Column(length: 255)]
    private ?string $administrateurLogiciel = null;

    #[ORM\Column(length: 100)]
    private ?string $hebergement = null;

    #[ORM\Column]
    private ?bool $transfertHorsUE = false;

    // BLOC CONSERVATION ET SÉCURITÉ
    #[ORM\Column(length: 100)]
    private ?string $dureeBaseActive = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dureeBaseIntermediaire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texteReglementaire = null;

    #[ORM\Column]
    private ?bool $archivage = false;

    #[ORM\Column(type: Types::JSON)]
    private array $securitePhysique = [];

    #[ORM\Column(type: Types::JSON)]
    private array $controleAccesLogique = [];

    #[ORM\Column]
    private ?bool $tracabilite = false;

    #[ORM\Column]
    private ?bool $sauvegardesDonnees = false;

    #[ORM\Column]
    private ?bool $chiffrementAnonymisation = false;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $mecanismePurge = null;

    // BLOC DROITS ET CONFORMITÉ
    #[ORM\Column(type: Types::TEXT)]
    private ?string $droitsPersonnes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentMention = null;

    #[ORM\Column(length: 50)]
    private ?string $etatTraitement = null;

    // MÉTADONNÉES
    #[ORM\ManyToOne(inversedBy: 'treatments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /**
     * Identifiant anonymisé de l'utilisateur créateur (utilisé quand l'utilisateur est supprimé)
     * Ex: "Utilisateur supprimé #12345"
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $createdByAnonymized = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $raisonRefus = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateArchivage = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->etatTraitement = 'En cours';
    }

    // GETTERS ET SETTERS
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResponsableTraitement(): ?string
    {
        return $this->responsableTraitement;
    }

    public function setResponsableTraitement(string $responsableTraitement): static
    {
        $this->responsableTraitement = $responsableTraitement;
        return $this;
    }

    public function getAdressePostale(): ?string
    {
        return $this->adressePostale;
    }

    public function setAdressePostale(string $adressePostale): static
    {
        $this->adressePostale = $adressePostale;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getReferentRGPD(): ?string
    {
        return $this->referentRGPD;
    }

    public function setReferentRGPD(string $referentRGPD): static
    {
        $this->referentRGPD = $referentRGPD;
        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(string $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getNomTraitement(): ?string
    {
        return $this->nomTraitement;
    }

    public function setNomTraitement(string $nomTraitement): static
    {
        $this->nomTraitement = $nomTraitement;
        return $this;
    }

    public function getNumeroReference(): ?string
    {
        return $this->numeroReference;
    }

    public function setNumeroReference(?string $numeroReference): static
    {
        $this->numeroReference = $numeroReference;
        return $this;
    }

    public function getDerniereMiseAJour(): ?\DateTimeInterface
    {
        return $this->derniereMiseAJour;
    }

    public function setDerniereMiseAJour(\DateTimeInterface $derniereMiseAJour): static
    {
        $this->derniereMiseAJour = $derniereMiseAJour;
        return $this;
    }

    public function getFinalite(): ?string
    {
        return $this->finalite;
    }

    public function setFinalite(string $finalite): static
    {
        $this->finalite = $finalite;
        return $this;
    }

    public function getBaseJuridique(): ?string
    {
        return $this->baseJuridique;
    }

    public function setBaseJuridique(string $baseJuridique): static
    {
        $this->baseJuridique = $baseJuridique;
        return $this;
    }

    public function getCategoriePersonnes(): array
    {
        return $this->categoriePersonnes;
    }

    public function setCategoriePersonnes(array $categoriePersonnes): static
    {
        $this->categoriePersonnes = $categoriePersonnes;
        return $this;
    }

    public function getVolumePersonnes(): ?string
    {
        return $this->volumePersonnes;
    }

    public function setVolumePersonnes(?string $volumePersonnes): static
    {
        $this->volumePersonnes = $volumePersonnes;
        return $this;
    }

    public function getSourceDonnees(): array
    {
        return $this->sourceDonnees;
    }

    public function setSourceDonnees(array $sourceDonnees): static
    {
        $this->sourceDonnees = $sourceDonnees;
        return $this;
    }

    public function getDonneesPersonnelles(): array
    {
        return $this->donneesPersonnelles;
    }

    public function setDonneesPersonnelles(array $donneesPersonnelles): static
    {
        $this->donneesPersonnelles = $donneesPersonnelles;
        return $this;
    }

    public function getDonneesHautementPersonnelles(): ?array
    {
        return $this->donneesHautementPersonnelles;
    }

    public function setDonneesHautementPersonnelles(?array $donneesHautementPersonnelles): static
    {
        $this->donneesHautementPersonnelles = $donneesHautementPersonnelles;
        return $this;
    }

    public function isPersonnesVulnerables(): ?bool
    {
        return $this->personnesVulnerables;
    }

    public function setPersonnesVulnerables(bool $personnesVulnerables): static
    {
        $this->personnesVulnerables = $personnesVulnerables;
        return $this;
    }

    public function isDonneesPapier(): ?bool
    {
        return $this->donneesPapier;
    }

    public function setDonneesPapier(bool $donneesPapier): static
    {
        $this->donneesPapier = $donneesPapier;
        return $this;
    }

    public function getReferentOperationnel(): ?string
    {
        return $this->referentOperationnel;
    }

    public function setReferentOperationnel(string $referentOperationnel): static
    {
        $this->referentOperationnel = $referentOperationnel;
        return $this;
    }

    public function getDestinatairesInternes(): array
    {
        return $this->destinatairesInternes;
    }

    public function setDestinatairesInternes(array $destinatairesInternes): static
    {
        $this->destinatairesInternes = $destinatairesInternes;
        return $this;
    }

    public function getDestinatairesExternes(): ?array
    {
        return $this->destinatairesExternes;
    }

    public function setDestinatairesExternes(?array $destinatairesExternes): static
    {
        $this->destinatairesExternes = $destinatairesExternes;
        return $this;
    }

    public function getSousTraitant(): ?string
    {
        return $this->sousTraitant;
    }

    public function setSousTraitant(?string $sousTraitant): static
    {
        $this->sousTraitant = $sousTraitant;
        return $this;
    }

    public function getOutilInformatique(): ?string
    {
        return $this->outilInformatique;
    }

    public function setOutilInformatique(string $outilInformatique): static
    {
        $this->outilInformatique = $outilInformatique;
        return $this;
    }

    public function getAdministrateurLogiciel(): ?string
    {
        return $this->administrateurLogiciel;
    }

    public function setAdministrateurLogiciel(string $administrateurLogiciel): static
    {
        $this->administrateurLogiciel = $administrateurLogiciel;
        return $this;
    }

    public function getHebergement(): ?string
    {
        return $this->hebergement;
    }

    public function setHebergement(string $hebergement): static
    {
        $this->hebergement = $hebergement;
        return $this;
    }

    public function isTransfertHorsUE(): ?bool
    {
        return $this->transfertHorsUE;
    }

    public function setTransfertHorsUE(bool $transfertHorsUE): static
    {
        $this->transfertHorsUE = $transfertHorsUE;
        return $this;
    }

    public function getDureeBaseActive(): ?string
    {
        return $this->dureeBaseActive;
    }

    public function setDureeBaseActive(string $dureeBaseActive): static
    {
        $this->dureeBaseActive = $dureeBaseActive;
        return $this;
    }

    public function getDureeBaseIntermediaire(): ?string
    {
        return $this->dureeBaseIntermediaire;
    }

    public function setDureeBaseIntermediaire(?string $dureeBaseIntermediaire): static
    {
        $this->dureeBaseIntermediaire = $dureeBaseIntermediaire;
        return $this;
    }

    public function getTexteReglementaire(): ?string
    {
        return $this->texteReglementaire;
    }

    public function setTexteReglementaire(?string $texteReglementaire): static
    {
        $this->texteReglementaire = $texteReglementaire;
        return $this;
    }

    public function isArchivage(): ?bool
    {
        return $this->archivage;
    }

    public function setArchivage(bool $archivage): static
    {
        $this->archivage = $archivage;
        return $this;
    }

    public function getSecuritePhysique(): array
    {
        return $this->securitePhysique;
    }

    public function setSecuritePhysique(array $securitePhysique): static
    {
        $this->securitePhysique = $securitePhysique;
        return $this;
    }

    public function getControleAccesLogique(): array
    {
        return $this->controleAccesLogique;
    }

    public function setControleAccesLogique(array $controleAccesLogique): static
    {
        $this->controleAccesLogique = $controleAccesLogique;
        return $this;
    }

    public function isTracabilite(): ?bool
    {
        return $this->tracabilite;
    }

    public function setTracabilite(bool $tracabilite): static
    {
        $this->tracabilite = $tracabilite;
        return $this;
    }

    public function isSauvegardesDonnees(): ?bool
    {
        return $this->sauvegardesDonnees;
    }

    public function setSauvegardesDonnees(bool $sauvegardesDonnees): static
    {
        $this->sauvegardesDonnees = $sauvegardesDonnees;
        return $this;
    }

    public function isChiffrementAnonymisation(): ?bool
    {
        return $this->chiffrementAnonymisation;
    }

    public function setChiffrementAnonymisation(bool $chiffrementAnonymisation): static
    {
        $this->chiffrementAnonymisation = $chiffrementAnonymisation;
        return $this;
    }

    public function getMecanismePurge(): ?string
    {
        return $this->mecanismePurge;
    }

    public function setMecanismePurge(string $mecanismePurge): static
    {
        $this->mecanismePurge = $mecanismePurge;
        return $this;
    }

    public function getDroitsPersonnes(): ?string
    {
        return $this->droitsPersonnes;
    }

    public function setDroitsPersonnes(string $droitsPersonnes): static
    {
        $this->droitsPersonnes = $droitsPersonnes;
        return $this;
    }

    public function getDocumentMention(): ?string
    {
        return $this->documentMention;
    }

    public function setDocumentMention(?string $documentMention): static
    {
        $this->documentMention = $documentMention;
        return $this;
    }

    public function getEtatTraitement(): ?string
    {
        return $this->etatTraitement;
    }

    public function setEtatTraitement(string $etatTraitement): static
    {
        $this->etatTraitement = $etatTraitement;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getRaisonRefus(): ?string
    {
        return $this->raisonRefus;
    }

    public function setRaisonRefus(?string $raisonRefus): static
    {
        $this->raisonRefus = $raisonRefus;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeImmutable $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getDateArchivage(): ?\DateTimeImmutable
    {
        return $this->dateArchivage;
    }

    public function setDateArchivage(?\DateTimeImmutable $dateArchivage): static
    {
        $this->dateArchivage = $dateArchivage;
        return $this;
    }

    public function getCreatedByAnonymized(): ?string
    {
        return $this->createdByAnonymized;
    }

    public function setCreatedByAnonymized(?string $createdByAnonymized): static
    {
        $this->createdByAnonymized = $createdByAnonymized;
        return $this;
    }

    /**
     * Retourne le nom du créateur (utilisateur actuel ou anonymisé)
     */
    public function getCreatedByDisplay(): string
    {
        if ($this->createdBy !== null) {
            return $this->createdBy->getEmail();
        }

        return $this->createdByAnonymized ?? 'Utilisateur inconnu';
    }
}
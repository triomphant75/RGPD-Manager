import React, { useState, useEffect } from 'react';
import toast from 'react-hot-toast';

interface TreatmentFormProps {
  treatment?: any;
  onSubmit: (data: any, action: 'save' | 'submit') => Promise<void>;
  onCancel: () => void;
}

const TreatmentForm: React.FC<TreatmentFormProps> = ({ treatment, onSubmit, onCancel }) => {
  const [activeTab, setActiveTab] = useState(0);
  const [loading, setLoading] = useState(false);
  const [actionType, setActionType] = useState<'save' | 'submit' | null>(null);
  
  // Déterminer si le formulaire est modifiable
  const isEditable = !treatment || 
                     treatment.etatTraitement === 'Brouillon' || 
                     treatment.etatTraitement === 'A modifier' ||
                     treatment.etatTraitement === 'Archivé' ;
  
  const canSubmitToDPO = !treatment || 
                         treatment.etatTraitement === 'Brouillon' || 
                         treatment.etatTraitement === 'A modifier' ||
                         treatment.etatTraitement === 'Archivé';


  const [formData, setFormData] = useState({
    responsableTraitement: '',
    adressePostale: '',
    telephone: '',
    referentRGPD: '',
    service: '',
    nomTraitement: '',
    numeroReference: '',
    derniereMiseAJour: new Date().toISOString().split('T')[0],
    finalite: '',
    baseJuridique: '',
    categoriePersonnes: [] as string[],
    volumePersonnes: '',
    sourceDonnees: [] as string[],
    donneesPersonnelles: [] as string[],
    donneesHautementPersonnelles: [] as string[],
    personnesVulnerables: false,
    donneesPapier: false,
    referentOperationnel: '',
    destinatairesInternes: [] as string[],
    destinatairesExternes: [] as string[],
    sousTraitant: '',
    outilInformatique: '',
    administrateurLogiciel: '',
    hebergement: '',
    transfertHorsUE: false,
    dureeBaseActive: '',
    dureeBaseIntermediaire: '',
    texteReglementaire: '',
    archivage: false,
    securitePhysique: [] as string[],
    controleAccesLogique: [] as string[],
    tracabilite: false,
    sauvegardesDonnees: false,
    chiffrementAnonymisation: false,
    mecanismePurge: '',
    droitsPersonnes: '',
    documentMention: '',
  });

  useEffect(() => {
    if (treatment) {
      setFormData({
        responsableTraitement: treatment.responsableTraitement || '',
        adressePostale: treatment.adressePostale || '',
        telephone: treatment.telephone || '',
        referentRGPD: treatment.referentRGPD || '',
        service: treatment.service || '',
        nomTraitement: treatment.nomTraitement || '',
        numeroReference: treatment.numeroReference || '',
        derniereMiseAJour: treatment.derniereMiseAJour?.split('T')[0] || new Date().toISOString().split('T')[0],
        finalite: treatment.finalite || '',
        baseJuridique: treatment.baseJuridique || '',
        categoriePersonnes: treatment.categoriePersonnes || [],
        volumePersonnes: treatment.volumePersonnes || '',
        sourceDonnees: treatment.sourceDonnees || [],
        donneesPersonnelles: treatment.donneesPersonnelles || [],
        donneesHautementPersonnelles: treatment.donneesHautementPersonnelles || [],
        personnesVulnerables: treatment.personnesVulnerables || false,
        donneesPapier: treatment.donneesPapier || false,
        referentOperationnel: treatment.referentOperationnel || '',
        destinatairesInternes: treatment.destinatairesInternes || [],
        destinatairesExternes: treatment.destinatairesExternes || [],
        sousTraitant: treatment.sousTraitant || '',
        outilInformatique: treatment.outilInformatique || '',
        administrateurLogiciel: treatment.administrateurLogiciel || '',
        hebergement: treatment.hebergement || '',
        transfertHorsUE: treatment.transfertHorsUE || false,
        dureeBaseActive: treatment.dureeBaseActive || '',
        dureeBaseIntermediaire: treatment.dureeBaseIntermediaire || '',
        texteReglementaire: treatment.texteReglementaire || '',
        archivage: treatment.archivage || false,
        securitePhysique: treatment.securitePhysique || [],
        controleAccesLogique: treatment.controleAccesLogique || [],
        tracabilite: treatment.tracabilite || false,
        sauvegardesDonnees: treatment.sauvegardesDonnees || false,
        chiffrementAnonymisation: treatment.chiffrementAnonymisation || false,
        mecanismePurge: treatment.mecanismePurge || '',
        droitsPersonnes: treatment.droitsPersonnes || '',
        documentMention: treatment.documentMention || '',
      });
    }
  }, [treatment]);

  const tabs = [
    { id: 0, name: 'Identification', icon: 'bi-person-badge' },
    { id: 1, name: 'Description', icon: 'bi-file-text' },
    { id: 2, name: 'Acteurs & Accès', icon: 'bi-people' },
    { id: 3, name: 'Conservation & Sécurité', icon: 'bi-shield-lock' },
    { id: 4, name: 'Droits & Conformité', icon: 'bi-check-circle' },
  ];

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target;
    const checked = (e.target as HTMLInputElement).checked;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleMultiSelect = (field: string, value: string) => {
    setFormData(prev => {
      const currentValues = prev[field as keyof typeof prev] as string[];
      const newValues = currentValues.includes(value)
        ? currentValues.filter(v => v !== value)
        : [...currentValues, value];
      return { ...prev, [field]: newValues };
    });
  };

  const validateForm = (): string[] => {
    const errors: string[] = [];
    if (!formData.nomTraitement) errors.push('Nom du traitement');
    if (!formData.service) errors.push('Service');
    if (!formData.responsableTraitement) errors.push('Responsable');
    if (!formData.telephone) errors.push('Téléphone');
    if (!formData.adressePostale) errors.push('Adresse');
    if (!formData.referentRGPD) errors.push('Référent RGPD');
    if (!formData.finalite) errors.push('Finalité');
    if (!formData.baseJuridique) errors.push('Base juridique');
    if (formData.categoriePersonnes.length === 0) errors.push('Catégorie de personnes');
    if (formData.donneesPersonnelles.length === 0) errors.push('Données personnelles');
    if (!formData.referentOperationnel) errors.push('Référent opérationnel');
    if (!formData.outilInformatique) errors.push('Outil informatique');
    if (!formData.administrateurLogiciel) errors.push('Administrateur');
    if (!formData.hebergement) errors.push('Hébergement');
    if (!formData.dureeBaseActive) errors.push('Durée base active');
    if (!formData.mecanismePurge) errors.push('Mécanisme de purge');
    if (!formData.droitsPersonnes) errors.push('Droits des personnes');
    return errors;
  };

  const [showSubmitConfirm, setShowSubmitConfirm] = useState(false);

  const handleSave = async () => {
  setActionType('save');
  setLoading(true);
  try {
    await onSubmit(formData, 'save');
    
  } catch (error: any) {
    console.error('Erreur', error);
    const errorMessage = error.response?.data?.error || 
                        error.response?.data?.message ||
                        'Erreur lors de l\'enregistrement';
    toast.error(errorMessage, { duration: 5000 });
  } finally {
    setLoading(false);
    setActionType(null);
  }
};

const handleSubmitToDPO = async () => {
  const errors = validateForm();
  
  if (errors.length > 0) {
    // Remplacer alert par toast avec liste d'erreurs
    const errorList = errors.map(err => `• ${err}`).join('\n');
    
    toast.error(
      (t) => (
        <div className="text-sm">
          <p className="font-semibold mb-2">⚠️ Champs obligatoires manquants</p>
          <div className="text-xs space-y-1 max-h-48 overflow-y-auto">
            {errors.map((err, idx) => (
              <div key={idx} className="flex items-start">
                <span className="mr-2">•</span>
                <span>{err}</span>
              </div>
            ))}
          </div>
          <button
            onClick={() => toast.dismiss(t.id)}
            className="mt-3 w-full px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700"
          >
            Compris
          </button>
        </div>
      ),
      {
        duration: 10000,
        style: {
          minWidth: '350px',
          maxWidth: '450px',
        },
      }
    );
    return;
  }

  setShowSubmitConfirm(true);
};

// Nouvelle fonction pour confirmer la soumission
const confirmSubmitToDPO = async () => {
  setShowSubmitConfirm(false);
  setActionType('submit');
  setLoading(true);
  
  try {
    await onSubmit(formData, 'submit');
    
  } catch (error: any) {
    console.error('Erreur', error);
    const errorMessage = error.response?.data?.error || 
                        error.response?.data?.message ||
                        'Erreur lors de la soumission au DPO';
    toast.error(errorMessage, { duration: 5000 });
  } finally {
    setLoading(false);
    setActionType(null);
  }
};

  const MultiSelectField = ({ label, field, options }: { label: string; field: string; options: string[] }) => (
    <div>
      <label className="block text-sm font-medium mb-2">{label} *</label>
      <div className="border rounded-lg p-3 max-h-48 overflow-y-auto bg-gray-50">
        {options.map(option => (
          <label key={option} className="flex items-center py-1 cursor-pointer hover:bg-gray-100 px-2 rounded">
            <input
              type="checkbox"
              checked={(formData[field as keyof typeof formData] as string[]).includes(option)}
              onChange={() => handleMultiSelect(field, option)}
              disabled={!isEditable}
              className="rounded mr-2"
            />
            <span className="text-sm">{option}</span>
          </label>
        ))}
      </div>
    </div>
  );

  const renderTab = () => {
    switch (activeTab) {
      case 0:
        return (
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Responsable du traitement *</label>
                <input
                  type="text"
                  name="responsableTraitement"
                  value={formData.responsableTraitement}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Téléphone *</label>
                <input
                  type="tel"
                  name="telephone"
                  value={formData.telephone}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Adresse postale *</label>
              <textarea
                name="adressePostale"
                value={formData.adressePostale}
                onChange={handleChange}
                disabled={!isEditable}
                rows={3}
                className="w-full p-2 border rounded disabled:bg-gray-100"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Référent RGPD / DPO *</label>
                <input
                  type="text"
                  name="referentRGPD"
                  value={formData.referentRGPD}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Service *</label>
                <select
                  name="service"
                  value={formData.service}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                >
                  <option value="">Sélectionner...</option>
                  <option value="Ressources humaines">Ressources humaines</option>
                  <option value="Comptabilité">Comptabilité</option>
                  <option value="Communication">Communication</option>
                  <option value="Logistique">Logistique</option>
                  <option value="Informatique">Informatique</option>
                  <option value="Direction">Direction</option>
                </select>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Nom du traitement *</label>
                <input
                  type="text"
                  name="nomTraitement"
                  value={formData.nomTraitement}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">N° / Référence</label>
                <input
                  type="text"
                  name="numeroReference"
                  value={formData.numeroReference}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Dernière mise à jour *</label>
              <input
                type="date"
                name="derniereMiseAJour"
                value={formData.derniereMiseAJour}
                onChange={handleChange}
                disabled={!isEditable}
                className="w-full p-2 border rounded disabled:bg-gray-100"
              />
            </div>
          </div>
        );

      case 1:
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-1">Finalité principale et secondaires *</label>
              <textarea
                name="finalite"
                value={formData.finalite}
                onChange={handleChange}
                disabled={!isEditable}
                rows={4}
                className="w-full p-2 border rounded disabled:bg-gray-100"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Base juridique *</label>
              <select
                name="baseJuridique"
                value={formData.baseJuridique}
                onChange={handleChange}
                disabled={!isEditable}
                className="w-full p-2 border rounded disabled:bg-gray-100"
              >
                <option value="">Sélectionner...</option>
                <option value="Consentement">Consentement</option>
                <option value="Contrat">Contrat</option>
                <option value="Obligation légale">Obligation légale</option>
                <option value="Intérêt légitime">Intérêt légitime</option>
                <option value="Mission d'intérêt public">Mission d'intérêt public</option>
              </select>
            </div>
            <MultiSelectField
              label="Catégorie de personnes concernées"
              field="categoriePersonnes"
              options={['Salariés', 'Adhérents', 'Bénéficiaires', 'Donateurs', 'Prestataires', 'Patients', 'Clients', 'Fournisseurs']}
            />
            <div>
              <label className="block text-sm font-medium mb-1">Volume de personnes</label>
              <select
                name="volumePersonnes"
                value={formData.volumePersonnes}
                onChange={handleChange}
                disabled={!isEditable}
                className="w-full p-2 border rounded disabled:bg-gray-100"
              >
                <option value="">Sélectionner...</option>
                <option value="<50">Moins de 50</option>
                <option value="50-100">50 à 100</option>
                <option value="100-1000">100 à 1000</option>
                <option value=">1000">Plus de 1000</option>
              </select>
            </div>
            <MultiSelectField
              label="Source des données"
              field="sourceDonnees"
              options={['Formulaires internes', 'Candidatures', 'Bons de commande', 'Contrats', "Formulaire d'inscription en ligne"]}
            />
            <MultiSelectField
              label="Données personnelles"
              field="donneesPersonnelles"
              options={['État civil', 'Vie personnelle', 'Vie professionnelle', 'Économique', 'Connexion/logs', 'Cookies']}
            />
            <MultiSelectField
              label="Données hautement personnelles (sensibles)"
              field="donneesHautementPersonnelles"
              options={['Santé', 'Infractions', 'Opinions', 'Origine raciale', 'Biométrie', 'Sexualité']}
            />
            <div className="grid grid-cols-2 gap-4">
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  name="personnesVulnerables"
                  checked={formData.personnesVulnerables}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="rounded"
                />
                <span className="text-sm">Personnes vulnérables concernées</span>
              </label>
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  name="donneesPapier"
                  checked={formData.donneesPapier}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="rounded"
                />
                <span className="text-sm">Données papier</span>
              </label>
            </div>
          </div>
        );

      case 2:
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-1">Référent opérationnel *</label>
              <input
                type="text"
                name="referentOperationnel"
                value={formData.referentOperationnel}
                onChange={handleChange}
                disabled={!isEditable}
                className="w-full p-2 border rounded disabled:bg-gray-100"
              />
            </div>
            <MultiSelectField
              label="Destinataires internes"
              field="destinatairesInternes"
              options={['Direction', 'Service RH', 'Service Comptabilité', 'Service Communication', 'Service IT']}
            />
            <MultiSelectField
              label="Destinataires externes"
              field="destinatairesExternes"
              options={['Expert-comptable', 'Organisme de prévoyance', 'Prestataire emailing', 'Administration fiscale']}
            />
            <div>
              <label className="block text-sm font-medium mb-1">Sous-traitant</label>
              <input
                type="text"
                name="sousTraitant"
                value={formData.sousTraitant}
                onChange={handleChange}
                disabled={!isEditable}
                className="w-full p-2 border rounded disabled:bg-gray-100"
                placeholder="Nom du sous-traitant si applicable"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Outil informatique utilisé *</label>
                <input
                  type="text"
                  name="outilInformatique"
                  value={formData.outilInformatique}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Administrateur logiciel *</label>
                <input
                  type="text"
                  name="administrateurLogiciel"
                  value={formData.administrateurLogiciel}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Hébergement / localisation *</label>
                <select
                  name="hebergement"
                  value={formData.hebergement}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                >
                  <option value="">Sélectionner...</option>
                  <option value="Serveur interne">Serveur interne</option>
                  <option value="Cloud FR">Cloud FR</option>
                  <option value="Cloud UE">Cloud UE</option>
                  <option value="Cloud hors UE">Cloud hors UE</option>
                </select>
              </div>
              <div className="flex items-end pb-2">
                <label className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    name="transfertHorsUE"
                    checked={formData.transfertHorsUE}
                    onChange={handleChange}
                    disabled={!isEditable}
                    className="rounded"
                  />
                  <span className="text-sm font-medium">Transfert hors UE</span>
                </label>
              </div>
            </div>
          </div>
        );

      case 3:
        return (
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Durée en base active *</label>
                <input
                  type="text"
                  name="dureeBaseActive"
                  value={formData.dureeBaseActive}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                  placeholder="Ex: 5 ans, Durée du contrat + 2 ans"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Durée en base intermédiaire</label>
                <input
                  type="text"
                  name="dureeBaseIntermediaire"
                  value={formData.dureeBaseIntermediaire}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="w-full p-2 border rounded disabled:bg-gray-100"
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Texte réglementaire associé</label>
              <textarea
                name="texteReglementaire"
                value={formData.texteReglementaire}
                onChange={handleChange}
                disabled={!isEditable}
                rows={2}
                className="w-full p-2 border rounded disabled:bg-gray-100"
                placeholder="Ex: Code du travail - Articles L1234-19"
              />
            </div>
            <label className="flex items-center space-x-2">
              <input
                type="checkbox"
                name="archivage"
                checked={formData.archivage}
                onChange={handleChange}
                disabled={!isEditable}
                className="rounded"
              />
              <span className="text-sm">Archivage</span>
            </label>
            <MultiSelectField
              label="Sécurité physique"
              field="securitePhysique"
              options={['Locaux fermés à clé', 'Armoires sécurisées', 'Salle serveur sécurisée', 'Contrôle d\'accès biométrique']}
            />
            <MultiSelectField
              label="Contrôle d'accès logique"
              field="controleAccesLogique"
              options={['Authentification forte', 'Gestion des habilitations', 'VPN', 'Gestion des droits', 'Accès limité au service']}
            />
            <div className="grid grid-cols-3 gap-4">
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  name="tracabilite"
                  checked={formData.tracabilite}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="rounded"
                />
                <span className="text-sm">Traçabilité</span>
              </label>
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  name="sauvegardesDonnees"
                  checked={formData.sauvegardesDonnees}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="rounded"
                />
                <span className="text-sm">Sauvegardes</span>
              </label>
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  name="chiffrementAnonymisation"
                  checked={formData.chiffrementAnonymisation}
                  onChange={handleChange}
                  disabled={!isEditable}
                  className="rounded"
                />
                <span className="text-sm">Chiffrement</span>
              </label>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Mécanisme de purge *</label>
              <textarea
                name="mecanismePurge"
                value={formData.mecanismePurge}
                onChange={handleChange}
                disabled={!isEditable}
                rows={3}
                className="w-full p-2 border rounded disabled:bg-gray-100"
                placeholder="Ex: Suppression automatique après expiration des délais légaux"
              />
            </div>
          </div>
        );

      case 4:
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-1">Droits des personnes concernées *</label>
              <textarea
                name="droitsPersonnes"
                value={formData.droitsPersonnes}
                onChange={handleChange}
                disabled={!isEditable}
                rows={4}
                className="w-full p-2 border rounded disabled:bg-gray-100"
                placeholder="Ex: Droit d'accès, de rectification, d'opposition, de limitation du traitement"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Document contenant mention</label>
              <input
                type="text"
                name="documentMention"
                value={formData.documentMention}
                onChange={handleChange}
                disabled={!isEditable}
                className="w-full p-2 border rounded disabled:bg-gray-100"
                placeholder="Ex: Contrat de travail - Clause de confidentialité"
              />
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  // Badge d'état
  const renderStatusBadge = () => {
    if (!treatment) return null;
    
    const statusConfig: Record<string, { bg: string; text: string; icon: string }> = {
      'Brouillon': { bg: 'bg-gray-100', text: 'text-gray-800', icon: '📝' },
      'En validation': { bg: 'bg-blue-100', text: 'text-blue-800', icon: '⏳' },
      'Validé': { bg: 'bg-green-100', text: 'text-green-800', icon: '✅' },
      'Refusé': { bg: 'bg-red-100', text: 'text-red-800', icon: '❌' },
      'A modifier': { bg: 'bg-yellow-100', text: 'text-yellow-800', icon: '✏️' },
      'Archivé': { bg: 'bg-purple-100', text: 'text-purple-800', icon: '📦' },
    };

    const config = statusConfig[treatment.etatTraitement] || statusConfig['Brouillon'];

    return (
      <div className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${config.bg} ${config.text}`}>
        <span className="mr-1">{config.icon}</span>
        {treatment.etatTraitement}
      </div>
    );
  };

  return (
    <div className="max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg">
      <div className="mb-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-2xl font-bold text-gray-900">
            {treatment ? 'Modifier le traitement' : 'Nouveau traitement'}
          </h2>
          {renderStatusBadge()}
        </div>

        {/* Message d'information selon l'état */}
        {treatment?.etatTraitement === 'En validation' && (
          <div className="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
            <div className="flex">
              <i className="bi bi-info-circle text-blue-500 mr-3 text-xl"></i>
              <div>
                <p className="text-sm text-blue-700 font-medium">
                  Ce traitement est en cours de validation par le DPO.
                </p>
                <p className="text-sm text-blue-600 mt-1">
                  Vous ne pouvez pas le modifier tant qu'il n'a pas été validé ou refusé.
                </p>
              </div>
            </div>
          </div>
        )}

        {treatment?.etatTraitement === 'Validé' && (
          <div className="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
            <div className="flex">
              <i className="bi bi-check-circle text-green-500 mr-3 text-xl"></i>
              <div>
                <p className="text-sm text-green-700 font-medium">
                  Ce traitement a été validé par le DPO.
                </p>
                <p className="text-sm text-green-600 mt-1">
                  Il est maintenant en lecture seule et conforme au RGPD.
                </p>
              </div>
            </div>
          </div>
        )}

        

        {treatment?.etatTraitement === 'A modifier' && (
          <div className="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
            <div className="flex">
              <i className="bi bi-exclamation-triangle text-yellow-500 mr-3 text-xl"></i>
              <div>
                <p className="text-sm text-yellow-700 font-medium">
                  Le DPO demande des modifications avant validation.
                </p>
                <p className="text-sm text-yellow-600 mt-1">
                  Veuillez apporter les corrections nécessaires et soumettre à nouveau.
                </p>
              </div>
            </div>
          </div>
        )}

        {!treatment && (
          <p className="text-gray-600">
            Remplissez tous les champs obligatoires (*) pour créer un traitement conforme RGPD
          </p>
        )}
      </div>

      <div className="border-b border-gray-200 mb-6">
        <nav className="-mb-px flex space-x-4 overflow-x-auto">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`py-2 px-3 border-b-2 text-sm flex items-center space-x-2 whitespace-nowrap ${
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600 font-medium'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              <i className={`bi ${tab.icon}`}></i>
              <span>{tab.name}</span>
            </button>
          ))}
        </nav>
      </div>

      {renderTab()}

      <div className="flex justify-between items-center mt-8 pt-6 border-t">
        <div className="flex space-x-3">
          {activeTab > 0 && (
            <button
              onClick={() => setActiveTab(activeTab - 1)}
              className="px-4 py-2 border rounded-lg hover:bg-gray-50"
            >
              <i className="bi bi-arrow-left mr-2"></i>
              Précédent
            </button>
          )}
          {activeTab < tabs.length - 1 && (
            <button
              onClick={() => setActiveTab(activeTab + 1)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Suivant
              <i className="bi bi-arrow-right ml-2"></i>
            </button>
          )}
        </div>

        <div className="flex space-x-3">
          <button
            onClick={onCancel}
            className="px-4 py-2 border rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>

          {/* Bouton Enregistrer (Brouillon) */}
          {isEditable && (
            <button
              onClick={handleSave}
              disabled={loading}
              className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 flex items-center"
            >
              {loading && actionType === 'save' ? (
                <>
                  <i className="bi bi-arrow-clockwise animate-spin mr-2"></i>
                  Enregistrement...
                </>
              ) : (
                <>
                  <i className="bi bi-save mr-2"></i>
                  Enregistrer (Brouillon)
                </>
              )}
            </button>
          )}

          {/* Bouton Soumettre au DPO */}
          {canSubmitToDPO && (
            <button
              onClick={handleSubmitToDPO}
              disabled={loading}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center"
            >
              {loading && actionType === 'submit' ? (
                <>
                  <i className="bi bi-arrow-clockwise animate-spin mr-2"></i>
                  Soumission...
                </>
              ) : (
                <>
                  <i className="bi bi-send mr-2"></i>
                  Soumettre au DPO
                </>
              )}
            </button>
          )}
        </div>
        {/* Modale de confirmation de soumission au DPO */}
{showSubmitConfirm && (
  <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
      <div className="flex items-start mb-4">
        <div className="flex-shrink-0 bg-green-100 rounded-full p-3">
          <i className="bi bi-send text-green-600 text-2xl"></i>
        </div>
        <div className="ml-4">
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Soumettre au DPO ?
          </h3>
          <p className="text-sm text-gray-600">
            Vous êtes sur le point de soumettre ce traitement au DPO pour validation.
          </p>
        </div>
      </div>

      <div className="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
        <div className="flex">
          <i className="bi bi-info-circle text-blue-600 mr-3"></i>
          <div className="text-sm text-blue-700">
            <p className="font-medium mb-1">À savoir :</p>
            <ul className="space-y-1 text-xs">
              <li>• Vous ne pourrez plus modifier le traitement</li>
              <li>• Le DPO pourra le valider ou demander des modifications</li>
              <li>• Vous recevrez une notification de la décision</li>
            </ul>
          </div>
        </div>
      </div>

      <div className="flex justify-end space-x-3">
        <button
          onClick={() => setShowSubmitConfirm(false)}
          className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium"
        >
          Annuler
        </button>
        <button
          onClick={confirmSubmitToDPO}
          className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium flex items-center"
        >
          <i className="bi bi-send mr-2"></i>
          Confirmer la soumission
        </button>
      </div>
    </div>
  </div>
)}
      </div>
    </div>
  );
};

export { TreatmentForm };
export default TreatmentForm;
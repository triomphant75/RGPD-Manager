export interface User {
  id: string;
  email: string;
  role: 'admin' | 'user' | 'dpo';
  createdAt: string;
}

export interface Treatment {
  id: string;
  // Bloc Identification
  responsableTraitement: string;
  adressePostale: string;
  telephone: string;
  referentRGPD: string;
  service: 'Ressources humaines' | 'Comptabilité' | 'Communication' | 'Logistique' | 'Informatique' | 'Direction';
  nomTraitement: string;
  numeroReference?: string;
  derniereMiseAJour: string;
  
  // Bloc Description
  finalite: string;
  baseJuridique: 'Consentement' | 'Contrat' | 'Obligation légale' | 'Intérêt légitime' | 'Mission d\'intérêt public';
  categoriePersonnes: string[];
  volumePersonnes?: '<50' | '50–100' | '100–1000' | '>1000';
  sourceDonnees: string[];
  donneesPersonnelles: string[];
  donneesHautementPersonnelles?: string[];
  personnesVulnerables?: boolean;
  donneesPapier?: boolean;
  
  // Bloc Acteurs et accès
  referentOperationnel: string;
  destinatairesInternes: string[];
  destinatairesExternes?: string[];
  sousTraitant?: string;
  outilInformatique: string;
  administrateurLogiciel: string;
  hebergement: 'Serveur interne' | 'Cloud FR' | 'Cloud UE' | 'Cloud hors UE';
  transfertHorsUE: boolean;
  
  // Bloc Conservation et sécurité
  dureeBaseActive: string;
  dureeBaseIntermediaire?: string;
  texteReglementaire?: string;
  archivage?: boolean;
  securitePhysique: string[];
  controleAccesLogique: string[];
  tracabilite: boolean;
  sauvegardesDonnees: boolean;
  chiffrementAnonymisation: boolean;
  mecanismePurge: string;
  
  // Bloc Droits et conformité
  droitsPersonnes: string;
  documentMention?: string;
  etatTraitement: 'Brouillon' | 'En validation' | 'Validé' | 'A modifier' | 'Archivé';
  dateValidation?: string;
  dateArchivage?: string;
  createdBy: string;
  createdAt: string;
  updatedAt: string;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  setUser: (user: User) => void;
}

export interface TreatmentState {
  treatments: Treatment[];
  currentTreatment: Treatment | null;
  loading: boolean;
  error: string | null;
  fetchTreatments: () => Promise<void>;
  createTreatment: (treatment: Partial<Treatment>) => Promise<Treatment>; // ← MODIFIER ICI
  updateTreatment: (id: string, treatment: Partial<Treatment>) => Promise<Treatment>; // ← MODIFIER ICI
  deleteTreatment: (id: string) => Promise<void>;
  setCurrentTreatment: (treatment: Treatment | null) => void;
}

export interface Notification {
  id: number;
  type: 'treatment_submitted' | 'treatment_validated' | 'treatment_to_modify';
  title: string;
  message: string;
  treatment?: {
    id: string;
    nomTraitement: string;
  } | null;
  data?: {
    action?: string;
    treatmentId?: string;
    reason?: string;
    comment?: string;
  } | null;
  isRead: boolean;
  createdAt: string;
  readAt?: string | null;
}

export interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  loading: boolean;
}
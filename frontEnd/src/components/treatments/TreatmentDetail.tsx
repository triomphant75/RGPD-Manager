import React from 'react';
import { Treatment } from '../../types';
import { Card } from '../common/Card';
import { Button } from '../common/Button';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useAuthStore } from '../../store/authStore';

interface TreatmentDetailProps {
  treatment: Treatment;
  onEdit: () => void;
  onClose: () => void;
  onArchive: () => void;
}

export const TreatmentDetail: React.FC<TreatmentDetailProps> = ({
  treatment,
  onEdit,
  onClose,
  onArchive
}) => {
  const sections = [
    {
      title: 'Identification',
      icon: 'bi-person-badge',
      fields: [
        { label: 'Responsable du traitement', value: treatment.responsableTraitement },
        { label: 'Adresse postale', value: treatment.adressePostale },
        { label: 'Téléphone', value: treatment.telephone },
        { label: 'Référent RGPD / DPO', value: treatment.referentRGPD },
        { label: 'Service', value: treatment.service },
        { label: 'Nom du traitement', value: treatment.nomTraitement },
        { label: 'N° / Référence', value: treatment.numeroReference || 'Non renseigné' },
        { label: 'Dernière mise à jour', value: format(new Date(treatment.derniereMiseAJour), 'dd/MM/yyyy', { locale: fr }) },
      ]
    },
    {
      title: 'Description du traitement',
      icon: 'bi-file-text',
      fields: [
        { label: 'Finalité', value: treatment.finalite },
        { label: 'Base juridique', value: treatment.baseJuridique },
        { label: 'Catégorie de personnes concernées', value: treatment.categoriePersonnes?.join(', ') || 'Non renseigné' },
        { label: 'Volume de personnes concernées', value: treatment.volumePersonnes || 'Non renseigné' },
        { label: 'Source des données', value: treatment.sourceDonnees?.join(', ') || 'Non renseigné' },
        { label: 'Données personnelles', value: treatment.donneesPersonnelles?.join(', ') || 'Non renseigné' },
        { label: 'Données hautement personnelles', value: treatment.donneesHautementPersonnelles?.join(', ') || 'Aucune' },
        { label: 'Personnes vulnérables concernées', value: treatment.personnesVulnerables ? 'Oui' : 'Non' },
        { label: 'Données papier', value: treatment.donneesPapier ? 'Oui' : 'Non' },
      ]
    },
    {
      title: 'Acteurs et accès',
      icon: 'bi-people',
      fields: [
        { label: 'Référent opérationnel', value: treatment.referentOperationnel },
        { label: 'Destinataires internes', value: treatment.destinatairesInternes?.join(', ') || 'Non renseigné' },
        { label: 'Destinataires externes', value: treatment.destinatairesExternes?.join(', ') || 'Aucun' },
        { label: 'Sous-traitant', value: treatment.sousTraitant || 'Aucun' },
        { label: 'Outil informatique utilisé', value: treatment.outilInformatique },
        { label: 'Administrateur logiciel', value: treatment.administrateurLogiciel },
        { label: 'Hébergement / localisation', value: treatment.hebergement },
        { label: 'Transfert hors UE', value: treatment.transfertHorsUE ? 'Oui' : 'Non' },
      ]
    },
    {
      title: 'Conservation et sécurité',
      icon: 'bi-shield-lock',
      fields: [
        { label: 'Durée en base active', value: treatment.dureeBaseActive },
        { label: 'Durée en base intermédiaire', value: treatment.dureeBaseIntermediaire || 'Non applicable' },
        { label: 'Texte réglementaire associé', value: treatment.texteReglementaire || 'Non renseigné' },
        { label: 'Archivage', value: treatment.archivage ? 'Oui' : 'Non' },
        { label: 'Sécurité physique', value: treatment.securitePhysique?.join(', ') || 'Non renseigné' },
        { label: 'Contrôle d\'accès logique', value: treatment.controleAccesLogique?.join(', ') || 'Non renseigné' },
        { label: 'Traçabilité', value: treatment.tracabilite ? 'Oui' : 'Non' },
        { label: 'Sauvegarde des données', value: treatment.sauvegardesDonnees ? 'Oui' : 'Non' },
        { label: 'Chiffrement / anonymisation', value: treatment.chiffrementAnonymisation ? 'Oui' : 'Non' },
        { label: 'Mécanisme de purge', value: treatment.mecanismePurge },
      ]
    },
    {
      title: 'Droits et conformité',
      icon: 'bi-check-circle',
      fields: [
        { label: 'Droits des personnes concernées', value: treatment.droitsPersonnes },
        { label: 'Document contenant mention', value: treatment.documentMention || 'Non renseigné' },
        { label: 'État du traitement', value: treatment.etatTraitement },
      ]
    }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Validé':
        return 'bg-success-100 text-success-800';
      case 'En cours':
        return 'bg-accent-100 text-accent-800';
      case 'À revoir':
        return 'bg-danger-100 text-danger-800';
      case 'Supprimé':
        return 'bg-secondary-100 text-secondary-800';
      default:
        return 'bg-secondary-100 text-secondary-800';
    }
  };
  const { user } = useAuthStore();

  return (
    <div className="max-w-4xl mx-auto">
      <Card>
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold text-secondary-900 mb-2">
              {treatment.nomTraitement}
            </h1>
            <div className="flex items-center space-x-4">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-800">
                {treatment.service}
              </span>
              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(treatment.etatTraitement)}`}>
                {treatment.etatTraitement}
              </span>
            </div>
          </div>
          <div className="flex space-x-3">
            <Button variant="outline" onClick={onClose}>
              <i className="bi bi-arrow-left mr-2"></i>
              Retour
            </Button>
            {user?.role === 'admin' && treatment.etatTraitement === 'Validé' && (
            <Button 
              variant="secondary" 
              onClick={() => {
                if (confirm('⚠️ Êtes-vous sûr de vouloir archiver ce traitement ?\n\nUn traitement archivé ne peut plus être modifié ni désarchivé. Il restera consultable pour la traçabilité.')) {
                  onArchive();
                }
              }}
            >
              <i className="bi bi-archive mr-2"></i>
              Archiver
            </Button>
          )}

          {/* Bouton Modifier - RGPD: Seulement le propriétaire ou admin */}
          {(() => {
            // Ne pas afficher pour les traitements archivés, validés ou en validation
            if (treatment.etatTraitement === 'Archivé' ||
                treatment.etatTraitement === 'Validé' ||
                treatment.etatTraitement === 'En validation') {
              return null;
            }

            // SÉCURITÉ RGPD: Seul le propriétaire du traitement ou un admin peut modifier
            // Le DPO ne peut PAS modifier directement (seulement valider/refuser/demander modif)
            const isOwner = treatment.createdBy === user?.email;
            const isAdmin = user?.role === 'admin';

            if (!isOwner && !isAdmin) {
              return null;
            }

            return (
              <Button onClick={onEdit}>
                <i className="bi bi-pencil mr-2"></i>
                Modifier
              </Button>
            );
          })()}
          </div>
        </div>

        <div className="space-y-8">
          {sections.map((section, index) => (
            <div key={index} className="border-b border-secondary-200 pb-6 last:border-b-0">
              <h2 className="text-lg font-semibold text-secondary-900 mb-4 flex items-center">
                <i className={`bi ${section.icon} mr-2 text-primary-600`}></i>
                {section.title}
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {section.fields.map((field, fieldIndex) => (
                  <div key={fieldIndex} className="space-y-1">
                    <dt className="text-sm font-medium text-secondary-500">
                      {field.label}
                    </dt>
                    <dd className="text-sm text-secondary-900">
                      {field.value || 'Non renseigné'}
                    </dd>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>

        <div className="mt-8 pt-6 border-t border-secondary-200">
          <div className="text-sm text-secondary-500">
            <p>
              Créé le {format(new Date(treatment.createdAt), 'dd/MM/yyyy à HH:mm', { locale: fr })} par {treatment.createdBy}
            </p>
            <p>
              Dernière modification le {format(new Date(treatment.updatedAt), 'dd/MM/yyyy à HH:mm', { locale: fr })}
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
};

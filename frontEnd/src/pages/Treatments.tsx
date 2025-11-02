import React, { useEffect, useState } from 'react';
import { Layout } from '../components/layout/Layout';
import { TreatmentTable } from '../components/treatments/TreatmentTable';
import { TreatmentForm } from '../components/treatments/TreatmentForm';
import { TreatmentDetail } from '../components/treatments/TreatmentDetail';
import { Modal } from '../components/common/Modal';
import { Button } from '../components/common/Button';
import { useTreatmentStore } from '../store/treatmentStore';
import { Treatment } from '../types';
import api from '../services/api';
import toast from 'react-hot-toast';

export const Treatments: React.FC = () => {
  const {
    treatments,
    loading,
    fetchTreatments,
    createTreatment,
    updateTreatment,
    deleteTreatment,
  } = useTreatmentStore();

  const [showForm, setShowForm] = useState(false);
  const [showDetail, setShowDetail] = useState(false);
  const [selectedTreatment, setSelectedTreatment] = useState<Treatment | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [treatmentToDelete, setTreatmentToDelete] = useState<Treatment | null>(null);

  useEffect(() => {
    fetchTreatments();
  }, [fetchTreatments]);

  const handleView = (treatment: Treatment) => {
    setSelectedTreatment(treatment);
    setShowDetail(true);
  };

  const handleEdit = (treatment: Treatment) => {
    setSelectedTreatment(treatment);
    setIsEditing(true);
    setShowForm(true);
    setShowDetail(false);
  };

  const handleDelete = (treatment: Treatment) => {
    setTreatmentToDelete(treatment);
    setShowDeleteConfirm(true);
  };

  const handleArchive = async (treatment: Treatment) => {
  if (!confirm(
    `⚠️ Êtes-vous sûr de vouloir archiver le traitement "${treatment.nomTraitement}" ?\n\n` +
    `Un traitement archivé :\n` +
    `• Ne peut plus être modifié\n` +
    `• Ne peut pas être désarchivé\n` +
    `• Reste consultable pour la traçabilité\n\n` +
    `Cette action est irréversible.`
  )) {
    return;
  }

  try {
    await api.post(`/treatments/${treatment.id}/archive`);
    toast.success('📦 Traitement archivé avec succès');
    await fetchTreatments();
    setShowDetail(false);
  } catch (error: any) {
    const errorMessage = error.response?.data?.error || 'Erreur lors de l\'archivage';
    toast.error(errorMessage);
  }
};

  const confirmDelete = async () => {
    if (treatmentToDelete) {
      try {
        await deleteTreatment(treatmentToDelete.id);
        toast.success('Traitement supprimé avec succès');
        setShowDeleteConfirm(false);
        setTreatmentToDelete(null);
      } catch (error) {
        toast.error('Erreur lors de la suppression');
      }
    }
  };

  const handleFormSubmit = async (data: Partial<Treatment>, action: 'save' | 'submit') => {
  console.log('🎯 handleFormSubmit appelé', { action, isEditing, selectedTreatmentId: selectedTreatment?.id });
  
  try {
    let treatmentId: string;

    // Étape 1 : Créer ou mettre à jour le traitement
    if (isEditing && selectedTreatment) {
      console.log('Mise à jour du traitement existant:', selectedTreatment.id);
      await updateTreatment(selectedTreatment.id, data);
      treatmentId = selectedTreatment.id; // ← Utiliser l'ID du traitement sélectionné
      console.log('Traitement mis à jour avec ID:', treatmentId);
    } else {
      console.log('Création d\'un nouveau traitement');
      const newTreatment = await createTreatment(data);
      treatmentId = newTreatment.id;
      console.log('Traitement créé avec ID:', treatmentId);
    }

    // Étape 2 : Si l'action est "submit", soumettre au DPO
    if (action === 'submit') {
      console.log('🔔 Soumission au DPO pour le traitement:', treatmentId);
      
      try {
        const submitResponse = await api.post(`/treatments/${treatmentId}/submit`);
        console.log('Réponse de soumission:', submitResponse.data);
        
        toast.success('Traitement soumis au DPO pour validation', {
          duration: 4000,
          icon: '🔔',
        });
        
        // Rafraîchir la liste des traitements
        await fetchTreatments();
        
      } catch (submitError: any) {
        console.error('❌ Erreur lors de la soumission:', submitError);
        console.error('Response data:', submitError.response?.data);
        console.error('Response status:', submitError.response?.status);
        console.error('URL appelée:', submitError.config?.url);
        
        const errorMessage = submitError.response?.data?.error || 
                             submitError.response?.data?.message ||
                             'Erreur lors de la soumission au DPO';
        
        toast.error(errorMessage, { duration: 5000 });
        
        // Ne pas fermer le formulaire en cas d'erreur de soumission
        return;
      }
    } else {
      // Si c'est juste un save (brouillon)
      toast.success('💾 Traitement enregistré en brouillon');
    }

    // Étape 3 : Fermer le formulaire et réinitialiser
    console.log('🚪 Fermeture du formulaire');
    setShowForm(false);
    setSelectedTreatment(null);
    setIsEditing(false);

  } catch (error: any) {
    console.error('❌ Erreur globale:', error);
    console.error('Response:', error.response);

    if (error.response?.status === 409) {
      // Gestion des conflits (nom de traitement déjà existant)
       const errorMessage = error.response?.data?.error || 
                          'Un traitement avec ce nom existe déjà';
      toast.error(
        (t) => (
          <div className="text-sm">
            <p className="font-semibold mb-2">⚠️ Nom déjà utilisé</p>
            <p className="text-xs mb-3">{errorMessage}</p>
            <button
              onClick={() => toast.dismiss(t.id)}
              className="w-full px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700"
            >
              Compris
            </button>
          </div>
        ),
        {
          duration: 8000,
          style: {
            minWidth: '350px',
          },
        }
      );

      return;
    } else {
      const errorMessage = error.response?.data?.error || 
                          error.response?.data?.message ||
                          'Erreur lors de l\'enregistrement';
      
      toast.error(errorMessage, { duration: 5000 });
    }
    
    throw error;
  }
};

  const handleNewTreatment = () => {
    setSelectedTreatment(null);
    setIsEditing(false);
    setShowForm(true);
  };

  const handleCloseForm = () => {
    setShowForm(false);
    setSelectedTreatment(null);
    setIsEditing(false);
  };

  const handleCloseDetail = () => {
    setShowDetail(false);
    setSelectedTreatment(null);
  };


  // Fonctions d'export CSV et PDF




  if (showForm) {
    return (
      <Layout>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <TreatmentForm
            treatment={selectedTreatment || undefined}
            onSubmit={handleFormSubmit}
            onCancel={handleCloseForm}
          />
        </div>
      </Layout>
    );
  }

  if (showDetail && selectedTreatment) {
    return (
      <Layout>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <TreatmentDetail
            treatment={selectedTreatment}
            onEdit={() => handleEdit(selectedTreatment)}
            onClose={handleCloseDetail}
            onArchive={() => handleArchive(selectedTreatment)}
          />
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-secondary-900 mb-2">
              Registre des traitements
            </h1>
            <p className="text-secondary-600">
              Gérez tous vos traitements de données personnelles en conformité avec le RGPD
            </p>
          </div>
          <div className="flex space-x-3">
            {/* Boutons d'exportation (CSV, PDF) - À implémenter */}
            <Button onClick={handleNewTreatment}>
              <i className="bi bi-plus-circle mr-2"></i>
              Nouveau traitement
            </Button>
          </div>
        </div>

        {/* Table */}
        <TreatmentTable
          treatments={treatments}
          onView={handleView}
          onEdit={handleEdit}
          onDelete={handleDelete}
          loading={loading}
        />

        {/* Delete Confirmation Modal */}
        <Modal
          isOpen={showDeleteConfirm}
          onClose={() => setShowDeleteConfirm(false)}
          title="Confirmer la suppression"
        >
          <div className="space-y-4">
            <p className="text-secondary-700">
              Êtes-vous sûr de vouloir supprimer le traitement <strong>{treatmentToDelete?.nomTraitement}</strong> ?
            </p>
            <p className="text-sm text-danger-600">
              <i className="bi bi-exclamation-triangle mr-1"></i>
              Cette action est irréversible.
            </p>
            <div className="flex justify-end space-x-3">
              <Button variant="outline" onClick={() => setShowDeleteConfirm(false)}>
                Annuler
              </Button>
              <Button variant="danger" onClick={confirmDelete}>
                <i className="bi bi-trash mr-2"></i>
                Supprimer
              </Button>
            </div>
          </div>
        </Modal>
      </div>
    </Layout>
  );
};

import React, { useEffect, useState } from 'react';
import { Layout } from '../components/layout/Layout';
import { Card } from '../components/common/Card';
import api from '../services/api';
import toast from 'react-hot-toast';
import { Treatment } from '../types';

export const DPODashboard: React.FC = () => {
  const [pendingTreatments, setPendingTreatments] = useState<Treatment[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [selectedTreatment, setSelectedTreatment] = useState<Treatment | null>(null);
  const [showModifyModal, setShowModifyModal] = useState<boolean>(false);
  const [modifyComment, setModifyComment] = useState<string>('');

  const fetchPendingTreatments = async (): Promise<void> => {
    try {
      console.log('🔍 Récupération des traitements en attente...');
      const response = await api.get<Treatment[]>('/treatments/pending-validation');
      console.log('✅ Traitements reçus:', response.data);
      setPendingTreatments(response.data);
    } catch (error) {
      console.error('❌ Erreur lors du chargement:', error);
      toast.error('Erreur lors du chargement');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPendingTreatments();
  }, []);

  const handleValidate = async (id: string): Promise<void> => {
    try {
      await api.post(`/treatments/${id}/validate`);
      toast.success('✅ Traitement validé !');
      fetchPendingTreatments();
    } catch (error) {
      toast.error('❌ Erreur lors de la validation');
    }
  };

  // ❌ SUPPRIMÉ : handleReject complètement retiré

  const handleRequestModification = async (): Promise<void> => {
    if (!selectedTreatment) return;
    
    if (!modifyComment.trim()) {
      toast.error('Veuillez indiquer un commentaire');
      return;
    }

    try {
      await api.post(`/treatments/${selectedTreatment.id}/request-modification`, {
        comment: modifyComment
      });
      toast.success('✅ Demande de modification envoyée');
      setShowModifyModal(false);
      setModifyComment('');
      setSelectedTreatment(null);
      fetchPendingTreatments();
    } catch (error) {
      toast.error('❌ Erreur');
    }
  };

  return (
    <Layout>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-8">
          Tableau de bord DPO
        </h1>

        <Card>
          <h2 className="text-xl font-semibold mb-4">
            Traitements en attente de validation ({pendingTreatments.length})
          </h2>

          {loading ? (
            <div className="text-center py-8">
              <i className="bi bi-arrow-clockwise animate-spin text-2xl text-blue-600 mr-3"></i>
              <span className="text-gray-600">Chargement...</span>
            </div>
          ) : pendingTreatments.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <i className="bi bi-inbox text-4xl mb-4 block"></i>
              <p>Aucun traitement en attente</p>
            </div>
          ) : (
            <div className="space-y-4">
              {pendingTreatments.map(treatment => (
                <div key={treatment.id} className="border rounded-lg p-4 hover:bg-gray-50">
                  <div className="flex justify-between items-start">
                    <div>
                      <h3 className="font-semibold text-lg">{treatment.nomTraitement}</h3>
                      <p className="text-sm text-gray-600">{treatment.service}</p>
                      <p className="text-sm text-gray-500 mt-2">{treatment.finalite}</p>
                      <p className="text-xs text-gray-400 mt-1">
                        Créé par: {treatment.createdBy}
                      </p>
                    </div>
                    <div className="flex space-x-2">
                      <button
                        onClick={() => handleValidate(treatment.id)}
                        className="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700"
                      >
                        <i className="bi bi-check-lg mr-1"></i>
                        Valider
                      </button>
                      <button
                        onClick={() => {
                          setSelectedTreatment(treatment);
                          setShowModifyModal(true);
                        }}
                        className="px-3 py-1.5 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700"
                      >
                        <i className="bi bi-pencil mr-1"></i>
                        Demander modif
                      </button>
                      {/* ❌ SUPPRIMÉ : Bouton "Refuser" complètement retiré */}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>


        {/* Modal de demande de modification */}
        {showModifyModal && selectedTreatment && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 max-w-md w-full">
              <h3 className="text-lg font-semibold mb-4">Demander des modifications</h3>
              <p className="text-sm text-gray-600 mb-4">
                Traitement : <strong>{selectedTreatment.nomTraitement}</strong>
              </p>
              <textarea
                value={modifyComment}
                onChange={(e) => setModifyComment(e.target.value)}
                placeholder="Indiquez les modifications à apporter..."
                rows={4}
                className="w-full border rounded-lg p-3 mb-4"
              />
              <div className="flex justify-end space-x-3">
                <button
                  onClick={() => {
                    setShowModifyModal(false);
                    setModifyComment('');
                    setSelectedTreatment(null);
                  }}
                  className="px-4 py-2 border rounded-lg hover:bg-gray-50"
                >
                  Annuler
                </button>
                <button
                  onClick={handleRequestModification}
                  className="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
                >
                  Envoyer la demande
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </Layout>
  );
};
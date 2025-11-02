import React, { useState } from 'react';
import { Treatment } from '../../types';
import { Button } from '../common/Button';
import { Input } from '../common/Input';
import { Select } from '../common/Select';
import { Card } from '../common/Card';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useAuthStore } from '../../store/authStore';

interface TreatmentTableProps {
  treatments: Treatment[];
  onView: (treatment: Treatment) => void;
  onEdit: (treatment: Treatment) => void;
  onDelete: (treatment: Treatment) => void;
  loading?: boolean;
}

export const TreatmentTable: React.FC<TreatmentTableProps> = ({
  treatments,
  onView,
  onEdit,
  onDelete,
  loading = false,
}) => {
  const { user } = useAuthStore();
  const [searchTerm, setSearchTerm] = useState('');
  const [serviceFilter, setServiceFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const serviceOptions = [
    { value: '', label: 'Tous les services' },
    { value: 'Ressources humaines', label: 'Ressources humaines' },
    { value: 'Comptabilité', label: 'Comptabilité' },
    { value: 'Communication', label: 'Communication' },
    { value: 'Logistique', label: 'Logistique' },
    { value: 'Informatique', label: 'Informatique' },
    { value: 'Direction', label: 'Direction' },
  ];

  const statusOptions = [
    { value: '', label: 'Tous les états' },
    { value: 'Brouillon', label: 'Brouillon' },
    { value: 'En validation', label: 'En validation' },
    { value: 'Validé', label: 'Validé' },
    { value: 'A modifier', label: 'A modifier' },
    { value: 'Archivé', label: 'Archivé' },
  ];
  const filteredTreatments = treatments.filter(treatment => {
    const matchesSearch = treatment.nomTraitement.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         treatment.finalite.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesService = !serviceFilter || treatment.service === serviceFilter;
    const matchesStatus = !statusFilter || treatment.etatTraitement === statusFilter;
    
    return matchesSearch && matchesService && matchesStatus;
  });

  const totalPages = Math.ceil(filteredTreatments.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedTreatments = filteredTreatments.slice(startIndex, startIndex + itemsPerPage);

 const getStatusColor = (status: string) => {
  switch (status) {
    case 'Brouillon':
      return 'bg-gray-100 text-gray-800';
    case 'En validation':
      return 'bg-blue-100 text-blue-800';
    case 'Validé':
      return 'bg-success-100 text-success-800';
    case 'A modifier':
      return 'bg-yellow-100 text-yellow-800';
    case 'Archivé':
      return 'bg-secondary-100 text-secondary-800';
    default:
      return 'bg-secondary-100 text-secondary-800';
  }
};

  if (loading) {
    return (
      <Card>
        <div className="flex items-center justify-center py-12">
          <i className="bi bi-arrow-clockwise animate-spin text-2xl text-primary-600 mr-3"></i>
          <span className="text-secondary-600">Chargement des traitements...</span>
        </div>
      </Card>
    );
  }

  return (
    <Card>
      <div className="mb-6">
        <h2 className="text-xl font-semibold text-secondary-900 mb-4">
          <i className="bi bi-file-text mr-2"></i>
          Registre des traitements
        </h2>
        
        {/* Filtres */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <Input
            placeholder="Rechercher par nom ou finalité..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            icon="bi-search"
          />
          <Select
            options={serviceOptions}
            value={serviceFilter}
            onChange={(e) => setServiceFilter(e.target.value)}
            placeholder="Filtrer par service"
          />
          <Select
            options={statusOptions}
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            placeholder="Filtrer par état"
          />
        </div>
      </div>

      {paginatedTreatments.length === 0 ? (
        <div className="text-center py-12">
          <i className="bi bi-inbox text-4xl text-secondary-400 mb-4"></i>
          <p className="text-secondary-600">Aucun traitement trouvé</p>
        </div>
      ) : (
        <>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-secondary-200">
              <thead className="bg-secondary-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                    Nom du traitement
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                    Service
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                    Finalité
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                    État
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                    Dernière MAJ
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
             <tbody className="bg-white divide-y divide-secondary-200">
              {paginatedTreatments.map((treatment, index) => (
                <tr
                  key={treatment.id}
                  className={index % 2 === 0 ? 'bg-white' : 'bg-primary-50'}
                >
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-secondary-900">
                      {treatment.nomTraitement}
                    </div>
                    {treatment.numeroReference && (
                      <div className="text-sm text-secondary-500">
                        Réf: {treatment.numeroReference}
                      </div>
                    )}
                  </td>

                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-800">
                      {treatment.service}
                    </span>
                  </td>

                  <td className="px-6 py-4">
                    <div className="text-sm text-secondary-900 max-w-xs truncate">
                      {treatment.finalite}
                    </div>
                  </td>

                  <td className="px-6 py-4 whitespace-nowrap">
                    <span
                      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(
                        treatment.etatTraitement
                      )}`}
                    >
                      {treatment.etatTraitement}
                    </span>
                  </td>

                  <td className="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                    {format(new Date(treatment.derniereMiseAJour), 'dd/MM/yyyy', {
                      locale: fr,
                    })}
                  </td>

                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div className="flex items-center justify-end space-x-2">
                    {/* Bouton Voir - Tout le monde peut voir */}
                    <Button size="sm" variant="outline" onClick={() => onView(treatment)}>
                      <i className="bi bi-eye"></i>
                    </Button>

                    {/* Bouton Modifier - Admin OU propriétaire du traitement */}
                    {(() => {
                       // Ne pas afficher si archivé, validé ou en validation
                       if (treatment.etatTraitement === 'Archivé' ||
                           treatment.etatTraitement === 'Validé' ||
                           treatment.etatTraitement === 'En validation') {
                          return null;
                        }

                      // SÉCURITÉ RGPD:
                      // - Admin: peut modifier tous les traitements
                      // - Utilisateur normal: peut modifier UNIQUEMENT ses propres traitements
                      // - DPO: ne peut PAS modifier (seulement valider/refuser/demander modif)

                      const isAdmin = user?.role === 'admin';
                      const isOwner = treatment.createdBy === user?.email;

                      if (isAdmin || isOwner) {
                        return (
                          <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => onEdit(treatment)}
                          >
                            <i className="bi bi-pencil"></i>
                          </Button>
                        );
                      }

                      return null;
                    })()}

                    {/* Bouton Supprimer - Admin uniquement + Brouillon uniquement */}
                    {user?.role === 'admin' &&
                      treatment.etatTraitement === 'Brouillon' && (
                        <Button
                          size="sm"
                          variant="danger"
                          onClick={() => onDelete(treatment)}
                        >
                          <i className="bi bi-trash"></i>
                        </Button>
                      )}
                  </div>
                  </td>
                </tr>
              ))}
            </tbody>

            </table>
          </div>

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-between mt-6">
              <div className="text-sm text-secondary-700">
                Affichage de {startIndex + 1} à {Math.min(startIndex + itemsPerPage, filteredTreatments.length)} sur {filteredTreatments.length} résultats
              </div>
              <div className="flex items-center space-x-2">
                <Button
                  size="sm"
                  variant="outline"
                  disabled={currentPage === 1}
                  onClick={() => setCurrentPage(currentPage - 1)}
                >
                  <i className="bi bi-chevron-left"></i>
                </Button>
                <span className="text-sm text-secondary-700">
                  Page {currentPage} sur {totalPages}
                </span>
                <Button
                  size="sm"
                  variant="outline"
                  disabled={currentPage === totalPages}
                  onClick={() => setCurrentPage(currentPage + 1)}
                >
                  <i className="bi bi-chevron-right"></i>
                </Button>
              </div>
            </div>
          )}
        </>
      )}
    </Card>
  );
};

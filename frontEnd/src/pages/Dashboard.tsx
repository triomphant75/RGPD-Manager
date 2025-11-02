import React, { useEffect, useState } from 'react';
import { Layout } from '../components/layout/Layout';
import { Card } from '../components/common/Card';
import { Button } from '../components/common/Button';
import { useTreatmentStore } from '../store/treatmentStore';
import { useAuthStore } from '../store/authStore';

export const Dashboard: React.FC = () => {
  const { treatments, fetchTreatments } = useTreatmentStore();
  const { user } = useAuthStore();
  const [stats, setStats] = useState({
    total: 0,
    enCours: 0,
    valide: 0,
    aRevoir: 0,
  });

  useEffect(() => {
    fetchTreatments();
  }, [fetchTreatments]);

  useEffect(() => {
    const total = treatments.length;
    const enCours = treatments.filter(t => t.etatTraitement === 'Brouillon').length;
    const valide = treatments.filter(t => t.etatTraitement === 'Validé').length;
    const aRevoir = treatments.filter(t => t.etatTraitement === 'A modifier').length;

    setStats({ total, enCours, valide, aRevoir });
  }, [treatments]);

  const recentTreatments = treatments
    .sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime())
    .slice(0, 5);

  return (
    <Layout>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-secondary-900 mb-2">
            Tableau de bord
          </h1>
          <p className="text-secondary-600">
            Bienvenue {user?.email}, voici un aperçu de vos traitements RGPD
          </p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <Card className="text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-primary-100 rounded-lg mx-auto mb-4">
              <i className="bi bi-file-text text-primary-600 text-xl"></i>
            </div>
            <h3 className="text-2xl font-bold text-secondary-900 mb-1">{stats.total}</h3>
            <p className="text-secondary-600">Traitements total</p>
          </Card>

          <Card className="text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-accent-100 rounded-lg mx-auto mb-4">
              <i className="bi bi-clock text-accent-600 text-xl"></i>
            </div>
            <h3 className="text-2xl font-bold text-secondary-900 mb-1">{stats.enCours}</h3>
            <p className="text-secondary-600">Brouillon</p>
          </Card>

          <Card className="text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-success-100 rounded-lg mx-auto mb-4">
              <i className="bi bi-check-circle text-success-600 text-xl"></i>
            </div>
            <h3 className="text-2xl font-bold text-secondary-900 mb-1">{stats.valide}</h3>
            <p className="text-secondary-600">Validés</p>
          </Card>

          <Card className="text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-danger-100 rounded-lg mx-auto mb-4">
              <i className="bi bi-exclamation-triangle text-danger-600 text-xl"></i>
            </div>
            <h3 className="text-2xl font-bold text-secondary-900 mb-1">{stats.aRevoir}</h3>
            <p className="text-secondary-600">À modifier</p>
          </Card>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Recent Treatments */}
          <Card>
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-secondary-900">
                <i className="bi bi-clock-history mr-2"></i>
                Traitements récents
              </h2>
              <Button variant="outline" size="sm" onClick={() => window.location.href = '/treatments'}>
                Voir tout
              </Button>
            </div>

            {recentTreatments.length === 0 ? (
              <div className="text-center py-8">
                <i className="bi bi-inbox text-4xl text-secondary-400 mb-4"></i>
                <p className="text-secondary-600">Aucun traitement récent</p>
              </div>
            ) : (
              <div className="space-y-4">
                {recentTreatments.map((treatment) => (
                  <div key={treatment.id} className="flex items-center justify-between p-4 bg-secondary-50 rounded-lg">
                    <div className="flex-1">
                      <h3 className="font-medium text-secondary-900">{treatment.nomTraitement}</h3>
                      <p className="text-sm text-secondary-600">{treatment.service}</p>
                    </div>
                    <div className="flex items-center space-x-3">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        treatment.etatTraitement === 'Validé' ? 'bg-success-100 text-success-800' :
                        treatment.etatTraitement === 'En validation' ? 'bg-accent-100 text-accent-800' :
                        treatment.etatTraitement === 'A modifier' ? 'bg-danger-100 text-danger-800' :
                        'bg-secondary-100 text-secondary-800'
                      }`}>
                        {treatment.etatTraitement}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Card>

          {/* Quick Actions */}
          <Card>
            <h2 className="text-xl font-semibold text-secondary-900 mb-6">
              <i className="bi bi-lightning mr-2"></i>
              Actions rapides
            </h2>

            <div className="space-y-4">
              <Button
                className="w-full justify-start"
                onClick={() => window.location.href = '/treatments/new'}
              >
                <i className="bi bi-plus-circle mr-3"></i>
                Nouveau traitement
              </Button>

              <Button
                variant="outline"
                className="w-full justify-start"
                onClick={() => window.location.href = '/treatments'}
              >
                <i className="bi bi-file-text mr-3"></i>
                Consulter le registre
              </Button>

              <Button
                variant="outline"
                className="w-full justify-start"
                onClick={() => {
                  // Export functionality will be implemented
                  alert('Fonctionnalité d\'export en cours de développement');
                }}
              >
                <i className="bi bi-download mr-3"></i>
                Exporter le registre
              </Button>

              {user?.role === 'admin' && (
                <Button
                  variant="outline"
                  className="w-full justify-start"
                  onClick={() => window.location.href = '/users'}
                >
                  <i className="bi bi-people mr-3"></i>
                  Gérer les utilisateurs
                </Button>
              )}
            </div>
          </Card>
        </div>

        {/* Compliance Tips */}
        <Card className="mt-8">
          <h2 className="text-xl font-semibold text-secondary-900 mb-6">
            <i className="bi bi-lightbulb mr-2"></i>
            Conseils de conformité RGPD
          </h2>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0">
                <i className="bi bi-check-circle text-success-600 text-xl"></i>
              </div>
              <div>
                <h3 className="font-medium text-secondary-900 mb-1">Mise à jour régulière</h3>
                <p className="text-sm text-secondary-600">
                  Mettez à jour vos traitements au moins une fois par trimestre
                </p>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0">
                <i className="bi bi-shield-check text-primary-600 text-xl"></i>
              </div>
              <div>
                <h3 className="font-medium text-secondary-900 mb-1">Sécurité des données</h3>
                <p className="text-sm text-secondary-600">
                  Vérifiez régulièrement les mesures de sécurité mises en place
                </p>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0">
                <i className="bi bi-clock text-accent-600 text-xl"></i>
              </div>
              <div>
                <h3 className="font-medium text-secondary-900 mb-1">Durées de conservation</h3>
                <p className="text-sm text-secondary-600">
                  Respectez les durées de conservation définies pour chaque traitement
                </p>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </Layout>
  );
};

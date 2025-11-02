import React, { useState, useEffect } from 'react';
import { Layout } from '../components/layout/Layout';
import { Card } from '../components/common/Card';
import { Button } from '../components/common/Button';
import { Input } from '../components/common/Input';
import { Select } from '../components/common/Select';
import { Modal } from '../components/common/Modal';
import { useAuthStore } from '../store/authStore';
import { User } from '../types';
import { userAPI } from '../services/api';
import toast from 'react-hot-toast';

interface UserFormData {
  email: string;
  password: string;
  role: 'admin' | 'user' | 'dpo';
}

export const Users: React.FC = () => {
  const { user: currentUser } = useAuthStore();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [userToDelete, setUserToDelete] = useState<User | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // État du formulaire
  const [formData, setFormData] = useState<UserFormData>({
    email: '',
    password: '',
    role: 'user',
  });

  const [formErrors, setFormErrors] = useState<Partial<Record<keyof UserFormData, string>>>({});

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const response = await userAPI.getAll();
      setUsers(response.data);
    } catch (error) {
      toast.error('Erreur lors du chargement des utilisateurs');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (currentUser?.role === 'admin') {
      fetchUsers();
    }
  }, [currentUser]);

  const handleCreateUser = () => {
    setSelectedUser(null);
    setFormData({
      email: '',
      password: '',
      role: 'user',
    });
    setFormErrors({});
    setShowForm(true);
  };

  const handleEditUser = (user: User) => {
    setSelectedUser(user);
    setFormData({
      email: user.email,
      password: '', // Don't pre-fill password
      role: user.role,
    });
    setFormErrors({});
    setShowForm(true);
  };

  const handleDeleteUser = (user: User) => {
    setUserToDelete(user);
    setShowDeleteConfirm(true);
  };

  const handleChange = (field: keyof UserFormData, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    // Clear error when user types
    if (formErrors[field]) {
      setFormErrors(prev => ({ ...prev, [field]: undefined }));
    }
  };

  const validateForm = (): boolean => {
    const errors: Partial<Record<keyof UserFormData, string>> = {};

    // Email validation
    if (!formData.email) {
      errors.email = 'L\'email est obligatoire';
    } else if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(formData.email)) {
      errors.email = 'Adresse email invalide';
    }

    // Password validation (only for new users or if password is provided)
    if (!selectedUser && !formData.password) {
      errors.password = 'Le mot de passe est obligatoire';
    } else if (formData.password && formData.password.length < 6) {
      errors.password = 'Le mot de passe doit contenir au moins 6 caractères';
    }

    // Role validation
    if (!formData.role) {
      errors.role = 'Le rôle est obligatoire';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    setSubmitting(true);

    try {
      if (selectedUser) {
        // Update user
        const updateData: any = { email: formData.email, role: formData.role };
        if (formData.password) {
          updateData.password = formData.password;
        }
        await userAPI.update(selectedUser.id, updateData);
        toast.success('✅ Utilisateur mis à jour avec succès');
      } else {
        // Create user
        await userAPI.create(formData);
        toast.success('✅ Utilisateur créé avec succès');
      }
      setShowForm(false);
      fetchUsers();
    } catch (error: any) {
      const errorMessage = error.response?.data?.error || 
                          error.response?.data?.message ||
                          'Erreur lors de la sauvegarde';
      toast.error(errorMessage);
    } finally {
      setSubmitting(false);
    }
  };

  const confirmDelete = async () => {
    if (userToDelete) {
      try {
        await userAPI.delete(userToDelete.id);
        toast.success('✅ Utilisateur supprimé avec succès');
        setShowDeleteConfirm(false);
        setUserToDelete(null);
        fetchUsers();
      } catch (error: any) {
        const errorMessage = error.response?.data?.error || 'Erreur lors de la suppression';
        toast.error(errorMessage);
      }
    }
  };

  if (currentUser?.role !== 'admin') {
    return (
      <Layout>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <Card className="text-center py-12">
            <i className="bi bi-shield-exclamation text-4xl text-danger-500 mb-4"></i>
            <h2 className="text-xl font-semibold text-secondary-900 mb-2">
              Accès non autorisé
            </h2>
            <p className="text-secondary-600">
              Vous devez être administrateur pour accéder à cette page.
            </p>
          </Card>
        </div>
      </Layout>
    );
  }

  const roleOptions = [
    { value: 'user', label: '👤 Utilisateur' },
    { value: 'dpo', label: '🛡️ DPO (Délégué à la Protection des Données)' },
    { value: 'admin', label: '⚙️ Administrateur' },
  ];

  const getRoleBadge = (role: string) => {
    switch (role) {
      case 'admin':
        return { color: 'bg-red-100 text-red-800', icon: '⚙️', label: 'Administrateur' };
      case 'dpo':
        return { color: 'bg-blue-100 text-blue-800', icon: '🛡️', label: 'DPO' };
      case 'user':
        return { color: 'bg-gray-100 text-gray-800', icon: '👤', label: 'Utilisateur' };
      default:
        return { color: 'bg-gray-100 text-gray-800', icon: '👤', label: role };
    }
  };

  return (
    <Layout>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-secondary-900 mb-2">
              Gestion des utilisateurs
            </h1>
            <p className="text-secondary-600">
              Gérez les comptes utilisateurs et leurs permissions
            </p>
          </div>
          <Button onClick={handleCreateUser}>
            <i className="bi bi-person-plus mr-2"></i>
            Nouvel utilisateur
          </Button>
        </div>

        {/* Stats rapides */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <Card>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-secondary-600">Total utilisateurs</p>
                <p className="text-2xl font-bold text-secondary-900">{users.length}</p>
              </div>
              <i className="bi bi-people text-3xl text-primary-500"></i>
            </div>
          </Card>
          <Card>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-secondary-600">Administrateurs</p>
                <p className="text-2xl font-bold text-secondary-900">
                  {users.filter(u => u.role === 'admin').length}
                </p>
              </div>
              <i className="bi bi-shield-check text-3xl text-red-500"></i>
            </div>
          </Card>
          
        </div>

        {/* Users Table */}
        <Card>
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <i className="bi bi-arrow-clockwise animate-spin text-2xl text-primary-600 mr-3"></i>
              <span className="text-secondary-600">Chargement des utilisateurs...</span>
            </div>
          ) : users.length === 0 ? (
            <div className="text-center py-12">
              <i className="bi bi-inbox text-4xl text-secondary-400 mb-4"></i>
              <p className="text-secondary-600">Aucun utilisateur trouvé</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-secondary-200">
                <thead className="bg-secondary-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                      Utilisateur
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                      Rôle
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                      Date de création
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-secondary-200">
                  {users.map((user, index) => {
                    const badge = getRoleBadge(user.role);
                    return (
                      <tr key={user.id} className={index % 2 === 0 ? 'bg-white' : 'bg-primary-50'}>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center">
                            <div className="flex-shrink-0 h-10 w-10">
                              <div className="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                <i className="bi bi-person-circle text-primary-600 text-xl"></i>
                              </div>
                            </div>
                            <div className="ml-4">
                              <div className="text-sm font-medium text-secondary-900 flex items-center">
                                {user.email}
                                {user.id === currentUser?.id && (
                                  <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Vous
                                  </span>
                                )}
                              </div>
                              <div className="text-xs text-secondary-500">
                                ID: {user.id}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.color}`}>
                            <span className="mr-1">{badge.icon}</span>
                            {badge.label}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                          {new Date(user.createdAt).toLocaleDateString('fr-FR', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                          })}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <div className="flex items-center justify-end space-x-2">
                            <Button
                              size="sm"
                              variant="secondary"
                              onClick={() => handleEditUser(user)}
                              title="Modifier l'utilisateur"
                            >
                              <i className="bi bi-pencil"></i>
                            </Button>
                            {user.id !== currentUser?.id && (
                              <Button
                                size="sm"
                                variant="danger"
                                onClick={() => handleDeleteUser(user)}
                                title="Supprimer l'utilisateur"
                              >
                                <i className="bi bi-trash"></i>
                              </Button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </Card>

        {/* User Form Modal */}
        <Modal
          isOpen={showForm}
          onClose={() => setShowForm(false)}
          title={
            <div className="flex items-center">
              <i className={`bi ${selectedUser ? 'bi-pencil' : 'bi-person-plus'} mr-2 text-primary-600`}></i>
              {selectedUser ? `Modifier l'utilisateur` : 'Nouvel utilisateur'}
            </div>
          }
          size="md"
        >
          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Email */}
            <div>
              <label className="block text-sm font-medium text-secondary-700 mb-1">
                Adresse email <span className="text-danger-500">*</span>
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i className="bi bi-envelope text-secondary-400"></i>
                </div>
                <input
                  type="email"
                  value={formData.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  className={`
                    block w-full rounded-md border-secondary-300 shadow-sm
                    focus:border-primary-500 focus:ring-primary-500
                    pl-10 pr-3 py-2
                    ${formErrors.email ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500' : ''}
                  `}
                  placeholder="utilisateur@example.com"
                />
              </div>
              {formErrors.email && (
                <p className="mt-1 text-sm text-danger-600">
                  <i className="bi bi-exclamation-circle mr-1"></i>
                  {formErrors.email}
                </p>
              )}
            </div>

            {/* Password */}
            <div>
              <label className="block text-sm font-medium text-secondary-700 mb-1">
                {selectedUser ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'}
                {!selectedUser && <span className="text-danger-500"> *</span>}
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i className="bi bi-lock text-secondary-400"></i>
                </div>
                <input
                  type="password"
                  value={formData.password}
                  onChange={(e) => handleChange('password', e.target.value)}
                  className={`
                    block w-full rounded-md border-secondary-300 shadow-sm
                    focus:border-primary-500 focus:ring-primary-500
                    pl-10 pr-3 py-2
                    ${formErrors.password ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500' : ''}
                  `}
                  placeholder={selectedUser ? 'Laisser vide pour ne pas changer' : '••••••••'}
                />
              </div>
              {formErrors.password && (
                <p className="mt-1 text-sm text-danger-600">
                  <i className="bi bi-exclamation-circle mr-1"></i>
                  {formErrors.password}
                </p>
              )}
              {!selectedUser && (
                <p className="mt-1 text-xs text-secondary-500">
                  <i className="bi bi-info-circle mr-1"></i>
                  Minimum 6 caractères
                </p>
              )}
            </div>

            {/* Role */}
            <div>
              <label className="block text-sm font-medium text-secondary-700 mb-1">
                Rôle <span className="text-danger-500">*</span>
              </label>
              <select
                value={formData.role}
                onChange={(e) => handleChange('role', e.target.value as 'admin' | 'user' | 'dpo')}
                className={`
                  block w-full rounded-md border-secondary-300 shadow-sm
                  focus:border-primary-500 focus:ring-primary-500
                  px-3 py-2
                  ${formErrors.role ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500' : ''}
                `}
              >
                <option value="">Sélectionner un rôle...</option>
                {roleOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              {formErrors.role && (
                <p className="mt-1 text-sm text-danger-600">
                  <i className="bi bi-exclamation-circle mr-1"></i>
                  {formErrors.role}
                </p>
              )}
              <div className="mt-2 text-xs text-secondary-600 space-y-1">
                <p><strong>👤 Utilisateur:</strong> Peut créer et gérer ses propres traitements</p>
                <p><strong>🛡️ DPO:</strong> Peut valider/refuser tous les traitements</p>
                <p><strong>⚙️ Admin:</strong> Accès complet (utilisateurs, archivage, etc.)</p>
              </div>
            </div>

            {/* Info si modification */}
            {selectedUser && (
              <div className="bg-blue-50 border-l-4 border-blue-500 p-4">
                <div className="flex">
                  <i className="bi bi-info-circle text-blue-500 mr-3"></i>
                  <div className="text-sm text-blue-700">
                    <p className="font-medium">Modification d'un utilisateur existant</p>
                    <p className="mt-1">
                      Compte créé le {new Date(selectedUser.createdAt).toLocaleDateString('fr-FR')}
                    </p>
                  </div>
                </div>
              </div>
            )}

            <div className="flex justify-end space-x-3 pt-4 border-t">
              <Button 
                type="button" 
                variant="outline" 
                onClick={() => setShowForm(false)}
                disabled={submitting}
              >
                Annuler
              </Button>
              <Button 
                type="submit"
                disabled={submitting}
              >
                {submitting ? (
                  <>
                    <i className="bi bi-arrow-clockwise animate-spin mr-2"></i>
                    {selectedUser ? 'Mise à jour...' : 'Création...'}
                  </>
                ) : (
                  <>
                    <i className={`bi ${selectedUser ? 'bi-check-lg' : 'bi-plus-lg'} mr-2`}></i>
                    {selectedUser ? 'Mettre à jour' : 'Créer'}
                  </>
                )}
              </Button>
            </div>
          </form>
        </Modal>

        {/* Delete Confirmation Modal */}
        <Modal
          isOpen={showDeleteConfirm}
          onClose={() => setShowDeleteConfirm(false)}
          title={
            <div className="flex items-center text-danger-600">
              <i className="bi bi-exclamation-triangle mr-2"></i>
              Confirmer la suppression
            </div>
          }
        >
          <div className="space-y-4">
            <p className="text-secondary-700">
              Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{userToDelete?.email}</strong> ?
            </p>
            <div className="bg-danger-50 border-l-4 border-danger-500 p-4">
              <div className="flex">
                <i className="bi bi-exclamation-triangle text-danger-500 mr-3"></i>
                <div className="text-sm text-danger-700">
                  <p className="font-medium">Cette action est irréversible</p>
                  <p className="mt-1">
                    Tous les traitements créés par cet utilisateur resteront dans la base de données.
                  </p>
                </div>
              </div>
            </div>
            <div className="flex justify-end space-x-3">
              <Button variant="outline" onClick={() => setShowDeleteConfirm(false)}>
                Annuler
              </Button>
              <Button variant="danger" onClick={confirmDelete}>
                <i className="bi bi-trash mr-2"></i>
                Supprimer définitivement
              </Button>
            </div>
          </div>
        </Modal>
      </div>
    </Layout>
  );
};
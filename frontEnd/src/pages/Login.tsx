import React, { useState } from 'react';
import { useAuthStore } from '../store/authStore';
import { Card } from '../components/common/Card';
import toast from 'react-hot-toast';

interface LoginForm {
  email: string;
  password: string;
}

export const Login: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const { login } = useAuthStore();
  
  const [formData, setFormData] = useState<LoginForm>({
    email: '',
    password: '',
  });

  const [formErrors, setFormErrors] = useState<Partial<Record<keyof LoginForm, string>>>({});
  const [loginAttempts, setLoginAttempts] = useState(0);
  const [isLocked, setIsLocked] = useState(false);

  const handleChange = (field: keyof LoginForm, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    // Clear error when user types
    if (formErrors[field]) {
      setFormErrors(prev => ({ ...prev, [field]: undefined }));
    }
  };

  const validateForm = (): boolean => {
    const errors: Partial<Record<keyof LoginForm, string>> = {};

    // Email validation
    if (!formData.email) {
      errors.email = 'L\'adresse email est obligatoire';
    } else if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(formData.email)) {
      errors.email = 'Veuillez entrer une adresse email valide (ex: nom@domaine.com)';
    }

    // Password validation
    if (!formData.password) {
      errors.password = 'Le mot de passe est obligatoire';
    } else if (formData.password.length < 6) {
      errors.password = 'Le mot de passe doit contenir au moins 6 caractères';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // Check if locked
    if (isLocked) {
      toast.error('🔒 Trop de tentatives échouées. Veuillez réessayer dans quelques minutes.');
      return;
    }

    if (!validateForm()) {
      return;
    }

    console.log('Tentative de connexion:', { email: formData.email });
    
    setLoading(true);
    
    try {
      await login(formData.email, formData.password);
      
      // Reset attempts on success
      setLoginAttempts(0);
      
      toast.success('Connexion réussie !', {
        duration: 15000,
      });
      
      // Redirect after a short delay
      setTimeout(() => {
        window.location.href = '/dashboard';
      }, 500);
      
    } catch (error: any) {
      console.error('❌ Erreur de connexion:', error);
      
      const newAttempts = loginAttempts + 1;
      setLoginAttempts(newAttempts);

      // Lock after 5 failed attempts
      if (newAttempts >= 5) {
        setIsLocked(true);
        toast.error('🔒 Compte temporairement verrouillé après 5 tentatives échouées', {
          duration: 15000,
        });
        
        // Unlock after 5 minutes
        setTimeout(() => {
          setIsLocked(false);
          setLoginAttempts(0);
        }, 5 * 60 * 1000);
        
        setLoading(false);
        return;
      }

      // Déterminer le type d'erreur et afficher un message approprié
      const status = error?.response?.status;
      const errorData = error?.response?.data;
      
      let errorMessage = 'Une erreur est survenue lors de la connexion';
      let errorDetails = '';

      if (status === 401) {
        // Identifiants incorrects
        errorMessage = '❌ Identifiants incorrects';
        errorDetails = 'L\'adresse email ou le mot de passe est incorrect.';
        
        // Highlight both fields in red
        setFormErrors({
          email: 'Vérifiez votre adresse email',
          password: 'Vérifiez votre mot de passe',
        });

        toast.error(
          `${errorMessage}\n${errorDetails}\nTentatives restantes: ${5 - newAttempts}`,
          { duration: 15000 }
        );
        
      } else if (status === 404) {
        // Utilisateur non trouvé
        errorMessage = '📧 Compte introuvable';
        errorDetails = 'Aucun compte n\'existe avec cette adresse email.';
        
        setFormErrors({
          email: 'Cette adresse email n\'est pas enregistrée',
        });

        toast.error(`${errorMessage}\n${errorDetails}`, { duration: 15000 });
        
      } else if (status === 403) {
        // Compte désactivé/bloqué
        errorMessage = '🔒 Compte désactivé';
        errorDetails = 'Votre compte a été désactivé. Contactez un administrateur.';

        toast.error(`${errorMessage}\n${errorDetails}`, { duration: 15000 });

      } else if (status === 500) {
        // Erreur serveur
        errorMessage = '⚠️ Erreur serveur';
        errorDetails = 'Le serveur ne répond pas. Veuillez réessayer plus tard.';

        toast.error(`${errorMessage}\n${errorDetails}`, { duration: 15000 });

      } else if (error?.code === 'ERR_NETWORK') {
        // Pas de connexion réseau
        errorMessage = '🌐 Pas de connexion';
        errorDetails = 'Impossible de contacter le serveur. Vérifiez votre connexion internet.';

        toast.error(`${errorMessage}\n${errorDetails}`, { duration: 15000 });

      } else if (errorData?.error) {
        // Message d'erreur personnalisé du backend
        errorMessage = errorData.error;
        toast.error(errorMessage, { duration: 15000 });
        
      } else {
        // Erreur générique
        toast.error('Une erreur inattendue est survenue', { duration: 15000 });
      }

    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-50 via-white to-secondary-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        {/* Header */}
        <div className="text-center">
          <div className="flex justify-center">
            <div className="bg-primary-600 p-3 rounded-full shadow-lg">
              <i className="bi bi-shield-check text-white text-3xl"></i>
            </div>
          </div>
          <h2 className="mt-6 text-3xl font-bold text-secondary-900">
            RGPD Manager
          </h2>
          <p className="mt-2 text-sm text-secondary-600">
            Connectez-vous à votre compte pour gérer les traitements RGPD
          </p>
        </div>

        {/* Login Form */}
        <Card className="mt-8">
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Email Field */}
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-secondary-700 mb-1">
                Adresse email
                <span className="text-danger-500 ml-1">*</span>
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i className={`bi bi-envelope ${formErrors.email ? 'text-danger-400' : 'text-secondary-400'}`}></i>
                </div>
                <input
                  id="email"
                  type="email"
                  value={formData.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  placeholder="votre@email.com"
                  disabled={isLocked}
                  className={`
                    block w-full rounded-lg border shadow-sm
                    focus:ring-2 focus:ring-offset-0
                    pl-10 pr-3 py-2.5 text-sm
                    transition-colors duration-200
                    disabled:bg-gray-100 disabled:cursor-not-allowed
                    ${formErrors.email 
                      ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-200 bg-danger-50' 
                      : 'border-secondary-300 focus:border-primary-500 focus:ring-primary-200'
                    }
                  `}
                />
              </div>
              {formErrors.email && (
                <p className="text-sm text-danger-600 flex items-center mt-1.5">
                  <i className="bi bi-exclamation-circle mr-1.5"></i>
                  {formErrors.email}
                </p>
              )}
            </div>

            {/* Password Field */}
            <div>
              <label htmlFor="password" className="block text-sm font-medium text-secondary-700 mb-1">
                Mot de passe
                <span className="text-danger-500 ml-1">*</span>
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i className={`bi bi-lock ${formErrors.password ? 'text-danger-400' : 'text-secondary-400'}`}></i>
                </div>
                <input
                  id="password"
                  type="password"
                  value={formData.password}
                  onChange={(e) => handleChange('password', e.target.value)}
                  placeholder="••••••••"
                  disabled={isLocked}
                  className={`
                    block w-full rounded-lg border shadow-sm
                    focus:ring-2 focus:ring-offset-0
                    pl-10 pr-3 py-2.5 text-sm
                    transition-colors duration-200
                    disabled:bg-gray-100 disabled:cursor-not-allowed
                    ${formErrors.password 
                      ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-200 bg-danger-50' 
                      : 'border-secondary-300 focus:border-primary-500 focus:ring-primary-200'
                    }
                  `}
                />
              </div>
              {formErrors.password && (
                <p className="text-sm text-danger-600 flex items-center mt-1.5">
                  <i className="bi bi-exclamation-circle mr-1.5"></i>
                  {formErrors.password}
                </p>
              )}
            </div>

            {/* Failed attempts warning */}
            {loginAttempts > 0 && !isLocked && (
              <div className="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded">
                <div className="flex items-center">
                  <i className="bi bi-exclamation-triangle text-yellow-600 mr-2"></i>
                  <div className="text-sm text-yellow-700">
                    <p className="font-medium">
                      {loginAttempts === 1 ? 'Première tentative échouée' : `${loginAttempts} tentatives échouées`}
                    </p>
                    <p className="text-xs mt-0.5">
                      Tentatives restantes: {5 - loginAttempts}
                    </p>
                  </div>
                </div>
              </div>
            )}

            {/* Locked warning */}
            {isLocked && (
              <div className="bg-red-50 border-l-4 border-red-500 p-3 rounded">
                <div className="flex items-center">
                  <i className="bi bi-lock-fill text-red-600 mr-2"></i>
                  <div className="text-sm text-red-700">
                    <p className="font-medium">Compte temporairement verrouillé</p>
                    <p className="text-xs mt-0.5">
                      Trop de tentatives échouées. Réessayez dans 5 minutes.
                    </p>
                  </div>
                </div>
              </div>
            )}

            {/* Submit Button */}
            <button
              type="submit"
              disabled={loading || isLocked}
              className={`
                w-full flex justify-center items-center
                px-4 py-2.5 rounded-lg
                text-sm font-medium text-white
                transition-all duration-200
                focus:outline-none focus:ring-2 focus:ring-offset-2
                ${loading || isLocked
                  ? 'bg-gray-400 cursor-not-allowed'
                  : 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500 shadow-md hover:shadow-lg'
                }
              `}
            >
              {loading ? (
                <>
                  <i className="bi bi-arrow-clockwise animate-spin mr-2"></i>
                  Connexion en cours...
                </>
              ) : isLocked ? (
                <>
                  <i className="bi bi-lock-fill mr-2"></i>
                  Compte verrouillé
                </>
              ) : (
                <>
                  <i className="bi bi-box-arrow-in-right mr-2"></i>
                  Se connecter
                </>
              )}
            </button>
          </form>

          {/* Help Section */}
          <div className="mt-6 space-y-4">
            <div className="text-center">
              <p className="text-xs text-secondary-500">
                Problème de connexion ? Contactez votre administrateur
              </p>
            </div>

            

            {/* Security Tips */}
            <div className="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <p className="text-xs font-medium text-gray-700 mb-2">
                💡 Conseils de sécurité
              </p>
              <ul className="text-xs text-gray-600 space-y-1">
                <li className="flex items-start">
                  <span className="mr-1.5">•</span>
                  <span>Ne partagez jamais vos identifiants</span>
                </li>
                <li className="flex items-start">
                  <span className="mr-1.5">•</span>
                  <span>Déconnectez-vous après chaque session</span>
                </li>
              </ul>
            </div>
          </div>
        </Card>

        {/* Footer */}
        <div className="text-center">
          <p className="text-xs text-secondary-400">
            © 2024 RGPD Manager - Développé par{' '}
            <span className="text-primary-600 font-medium">Association Sainte Agnès</span>
          </p>
        </div>
      </div>
    </div>
  );
};
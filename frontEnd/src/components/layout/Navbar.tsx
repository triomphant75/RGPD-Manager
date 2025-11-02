import React, { useState } from 'react';
import { useAuthStore } from '../../store/authStore';
import { Button } from '../common/Button';
import { NotificationBell } from '../notifications/NotificationBell';

export const Navbar: React.FC = () => {
  const { user, logout } = useAuthStore();
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const handleLogout = () => {
    logout();
    window.location.href = '/login';
  };

  return (
    <nav className="bg-white shadow-soft border-b border-secondary-100 fixed top-0 left-0 right-0 z-40">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <div className="flex items-center">
            <div className="flex-shrink-0 flex items-center">
              <i className="bi bi-shield-check text-primary-600 text-2xl mr-3"></i>
              <h1 className="text-xl font-bold text-secondary-900">RGPD Manager</h1>
            </div>
          </div>

          <div className="hidden md:block">
            <div className="ml-10 flex items-baseline space-x-4">
              <a href="/dashboard" className="text-secondary-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                <i className="bi bi-house-door mr-2"></i>
                Tableau de bord
              </a>
              <a href="/treatments" className="text-secondary-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                <i className="bi bi-file-text mr-2"></i>
                Traitements
              </a>
              {/* Uniquement pour le DPO */}
              {user?.role === 'dpo' && (
                <a href="/dpo/dashboard" className="text-secondary-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                  <i className="bi bi-clipboard-check mr-2"></i>
                  Validation DPO
                </a>
              )}
              {user?.role === 'admin' && (
                <a href="/users" className="text-secondary-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                  <i className="bi bi-people mr-2"></i>
                  Utilisateurs
                </a>
              )}
            </div>
          </div>

          <div className="hidden md:block">
            <div className="ml-4 flex items-center md:ml-6 space-x-4">
              <NotificationBell />
              
              <div className="flex items-center space-x-3">
                <span className="text-sm text-secondary-700">
                  <i className="bi bi-person-circle mr-1"></i>
                  {user?.email}
                  {/* Badge de rôle pour DPO */}
                  {user?.role === 'dpo' && (
                    <span className="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                      DPO
                    </span>
                  )}
                  {user?.role === 'admin' && (
                    <span className="ml-2 text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded">
                      Admin
                    </span>
                  )}
                </span>
                <Button variant="outline" size="sm" onClick={handleLogout}>
                  <i className="bi bi-box-arrow-right mr-1"></i>
                  Déconnexion
                </Button>
              </div>
            </div>
          </div>

          <div className="md:hidden">
            <button
              onClick={() => setIsMenuOpen(!isMenuOpen)}
              className="text-secondary-700 hover:text-primary-600 p-2"
            >
              <i className={`bi bi-${isMenuOpen ? 'x' : 'list'} text-xl`}></i>
            </button>
          </div>
        </div>
      </div>

      {/* Menu mobile */}
      {isMenuOpen && (
        <div className="md:hidden">
          <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-secondary-100">
            <a href="/dashboard" className="text-secondary-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">
              <i className="bi bi-house-door mr-2"></i>
              Tableau de bord
            </a>
            <a href="/treatments" className="text-secondary-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">
              <i className="bi bi-file-text mr-2"></i>
              Traitements
            </a>
            {/* Uniquement pour le DPO */}
            {user?.role === 'dpo' && (
              <a href="/dpo/dashboard" className="text-secondary-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">
                <i className="bi bi-clipboard-check mr-2"></i>
                Validation DPO
              </a>
            )}
            {user?.role === 'admin' && (
              <a href="/users" className="text-secondary-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">
                <i className="bi bi-people mr-2"></i>
                Utilisateurs
              </a>
            )}
            <div className="border-t border-secondary-200 pt-4 pb-3">
              <div className="flex items-center px-3">
                <span className="text-sm text-secondary-700 mb-2 block">
                  <i className="bi bi-person-circle mr-1"></i>
                  {user?.email}
                  {user?.role === 'dpo' && (
                    <span className="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                      DPO
                    </span>
                  )}
                  {user?.role === 'admin' && (
                    <span className="ml-2 text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded">
                      Admin
                    </span>
                  )}
                </span>
              </div>
              <Button variant="outline" size="sm" onClick={handleLogout} className="mx-3">
                <i className="bi bi-box-arrow-right mr-1"></i>
                Déconnexion
              </Button>
            </div>
          </div>
        </div>
      )}
    </nav>
  );
};
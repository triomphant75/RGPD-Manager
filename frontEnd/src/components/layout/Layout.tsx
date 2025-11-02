import React from 'react';
import { Navbar } from './Navbar';

interface LayoutProps {
  children: React.ReactNode;
}

export const Layout: React.FC<LayoutProps> = ({ children }) => {
  return (
    <div className="min-h-screen bg-secondary-50">
      <Navbar />
      <main className="pt-16">
        {children}
      </main>
      <footer className="bg-white border-t border-secondary-200 py-4 mt-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center text-sm text-secondary-500">
            <p>© 2024 RGPD Manager - Copyright by <span className="text-primary-600 font-medium">Association Sainte Agnès</span></p>
          </div>
        </div>
      </footer>
    </div>
  );
};

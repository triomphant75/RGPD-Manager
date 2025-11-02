import axios from 'axios';
import { Treatment, User } from '../types';

// ✅ CORRECTION : Backend Symfony tourne sur port 8000
const API_BASE_URL = 'http://localhost:8000/api';

// Fonction pour récupérer un cookie par son nom
const getCookie = (name: string): string | null => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop()?.split(';').shift() || null;
  return null;
};

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Intercepteur pour ajouter le token d'authentification
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth-storage');
  const csrfToken = getCookie('XSRF-TOKEN');

  if (token) {
    const parsedToken = JSON.parse(token);
    if (parsedToken.state?.token) {
      config.headers.Authorization = `Bearer ${parsedToken.state.token}`;
    }

  }
    if (csrfToken) {
    config.headers['X-XSRF-TOKEN'] = csrfToken;
  }

  return config;
});

// Intercepteur pour gérer les erreurs
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expiré ou invalide
      localStorage.removeItem('auth-storage');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const authAPI = {
  login: (email: string, password: string) =>
    api.post('/auth/login', { email, password }),
  register: (email: string, password: string, role: string) =>
    api.post('/auth/register', { email, password, role }),
  me: () => api.get('/auth/me'),
};

export const treatmentAPI = {
  getAll: () => api.get<Treatment[]>('/treatments'),
  getById: (id: string) => api.get<Treatment>(`/treatments/${id}`),
  create: (treatment: Partial<Treatment>) => api.post<Treatment>('/treatments', treatment),
  update: (id: string, treatment: Partial<Treatment>) => 
    api.put<Treatment>(`/treatments/${id}`, treatment),
  delete: (id: string) => api.delete(`/treatments/${id}`),
  submitForValidation: (id: string) => api.post(`/treatments/${id}/submit`),
  validate: (id: string) => api.post(`/treatments/${id}/validate`),
  requestModification: (id: string, comment: string) => 
    api.post(`/treatments/${id}/request-modification`, { comment }),
  archive: (id: string) => api.post(`/treatments/${id}/archive`)
};

export const userAPI = {
  getAll: () => api.get<User[]>('/users'),
  create: (user: Partial<User>) => api.post<User>('/users', user),
  update: (id: string, user: Partial<User>) => api.put<User>(`/users/${id}`, user),
  delete: (id: string) => api.delete(`/users/${id}`),
};

export default api;
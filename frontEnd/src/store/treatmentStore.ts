import { create } from 'zustand';
import { TreatmentState, Treatment } from '../types';
import { treatmentAPI } from '../services/api';

export const useTreatmentStore = create<TreatmentState>((set, get) => ({
  treatments: [],
  currentTreatment: null,
  loading: false,
  error: null,

  fetchTreatments: async () => {
    set({ loading: true, error: null });
    try {
      const response = await treatmentAPI.getAll();
      set({ treatments: response.data, loading: false });
    } catch (error) {
      set({ error: 'Erreur lors du chargement des traitements', loading: false });
    }
  },

  createTreatment: async (treatment: Partial<Treatment>) => {
    set({ loading: true, error: null });
    try {
      const response = await treatmentAPI.create(treatment);
      const newTreatment = response.data;
      set(state => ({
        treatments: [...state.treatments, newTreatment],
        loading: false
      }));
      return newTreatment; // ← CETTE LIGNE EST ESSENTIELLE !
    } catch (error) {
      set({ error: 'Erreur lors de la création du traitement', loading: false });
      throw error;
    }
  },

  updateTreatment: async (id: string, treatment: Partial<Treatment>) => {
    set({ loading: true, error: null });
    try {
      const response = await treatmentAPI.update(id, treatment);
      const updatedTreatment = response.data;
      set(state => ({
        treatments: state.treatments.map(t => t.id === id ? updatedTreatment : t),
        currentTreatment: state.currentTreatment?.id === id ? updatedTreatment : state.currentTreatment,
        loading: false
      }));
      return updatedTreatment; // ← CETTE LIGNE EST ESSENTIELLE !
    } catch (error) {
      set({ error: 'Erreur lors de la mise à jour du traitement', loading: false });
      throw error;
    }
  },

  deleteTreatment: async (id: string) => {
    set({ loading: true, error: null });
    try {
      await treatmentAPI.delete(id);
      set(state => ({
        treatments: state.treatments.filter(t => t.id !== id),
        currentTreatment: state.currentTreatment?.id === id ? null : state.currentTreatment,
        loading: false
      }));
    } catch (error) {
      set({ error: 'Erreur lors de la suppression du traitement', loading: false });
      throw error;
    }
  },

  setCurrentTreatment: (treatment: Treatment | null) => {
    set({ currentTreatment: treatment });
  },
}));
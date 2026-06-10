import { create } from 'zustand';
import { statsService } from '../services/statsService';

const useDashboardStore = create((set) => ({
  dashboard: null,
  isLoading: false,
  error: null,

  fetchDashboard: async (dateStr) => {
    set({ isLoading: true, error: null });
    try {
      const dashboard = await statsService.dashboard(dateStr);
      set({ dashboard, isLoading: false });
    } catch (error) {
      set({ isLoading: false, error });
    }
  },
}));

export default useDashboardStore;

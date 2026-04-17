import { create } from 'zustand';
import axiosClient from '../services/axiosClient';

const useDashboardStore = create((set) => ({
  dashboard: null,
  isLoading: false,

  fetchDashboard: async (dateStr) => {
    set({ isLoading: true });
    try {
      const { data } = await axiosClient.get('/dashboard', {
        params: dateStr ? { date: dateStr } : {}
      });
      set({ dashboard: data, isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      console.error("Error fetching dashboard", error);
    }
  }
}));

export default useDashboardStore;

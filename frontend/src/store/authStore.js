import { create } from 'zustand';
import axiosClient from '../services/axiosClient';

const useAuthStore = create((set) => ({
  user: null,
  token: localStorage.getItem('auth_token'),
  isLoading: false,
  isInitialized: false,

  setUser: (user) => set({ user }),
  setToken: (token) => {
    if (token) {
      localStorage.setItem('auth_token', token);
    } else {
      localStorage.removeItem('auth_token');
    }
    set({ token });
  },

  login: async (email, password) => {
    set({ isLoading: true });
    try {
      const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://myfitnesspal.test/api';
      // CSRF cookie request
      await axiosClient.get(`${baseUrl.replace('/api', '')}/sanctum/csrf-cookie`, {
         baseURL: '' // Prevent appending to /api for the cookie route
      });
      
      const { data } = await axiosClient.post('/login', { email, password });
      set({ user: data.user, token: data.access_token, isLoading: false });
      localStorage.setItem('auth_token', data.access_token);
      return data;
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  register: async (name, email, password) => {
    set({ isLoading: true });
    try {
      const { data } = await axiosClient.post('/register', { name, email, password });
      set({ user: data.user, token: data.access_token, isLoading: false });
      localStorage.setItem('auth_token', data.access_token);
      return data;
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await axiosClient.post('/logout');
    } finally {
      set({ user: null, token: null, isLoading: false });
      localStorage.removeItem('auth_token');
    }
  },

  fetchUser: async () => {
    try {
      const { data } = await axiosClient.get('/user');
      set({ user: data });
    } catch (error) {
      set({ user: null, token: null });
      localStorage.removeItem('auth_token');
    } finally {
      set({ isInitialized: true });
    }
  }
}));

export default useAuthStore;

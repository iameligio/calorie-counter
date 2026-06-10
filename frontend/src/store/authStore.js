import { create } from 'zustand';
import axiosClient from '../services/axiosClient';

const TOKEN_KEY = 'auth_token';

/**
 * Authentication state. The Bearer token is the single source of truth and is
 * mirrored to localStorage exclusively through `setToken` so persistence never
 * drifts from in-memory state.
 */
const useAuthStore = create((set, get) => ({
  user: null,
  token: localStorage.getItem(TOKEN_KEY),
  isLoading: false,
  isInitialized: false,

  setUser: (user) => set({ user }),

  setToken: (token) => {
    if (token) {
      localStorage.setItem(TOKEN_KEY, token);
    } else {
      localStorage.removeItem(TOKEN_KEY);
    }
    set({ token });
  },

  login: async (email, password) => {
    set({ isLoading: true });
    try {
      const { data } = await axiosClient.post('/login', { email, password });
      get().setToken(data.access_token);
      set({ user: data.user });
      return data;
    } finally {
      set({ isLoading: false });
    }
  },

  register: async (name, email, password) => {
    set({ isLoading: true });
    try {
      const { data } = await axiosClient.post('/register', { name, email, password });
      get().setToken(data.access_token);
      set({ user: data.user });
      return data;
    } finally {
      set({ isLoading: false });
    }
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await axiosClient.post('/logout');
    } finally {
      get().setToken(null);
      set({ user: null, isLoading: false });
    }
  },

  fetchUser: async () => {
    try {
      const { data } = await axiosClient.get('/user');
      set({ user: data });
    } catch {
      get().setToken(null);
      set({ user: null });
    } finally {
      set({ isInitialized: true });
    }
  },
}));

export default useAuthStore;

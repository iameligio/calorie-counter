import axiosClient from './axiosClient';

/**
 * Food catalog API — system foods plus the current user's custom foods.
 */
export const foodService = {
  async search(query) {
    const { data } = await axiosClient.get('/foods', { params: { search: query } });
    return data.data;
  },

  async create(payload) {
    const { data } = await axiosClient.post('/foods', payload);
    return data;
  },
};

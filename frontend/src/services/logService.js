import axiosClient from './axiosClient';

/**
 * Food-log API. Returns `{ logs, summary }` for ranges, and bare entries for writes.
 */
export const logService = {
  async list(params = {}) {
    const { data } = await axiosClient.get('/logs', { params });
    return {
      logs: data.logs ?? [],
      summary: data.summary ?? { total_calories: 0, total_target: 0, days_count: 1 },
    };
  },

  async create({ foodId, grams }) {
    const { data } = await axiosClient.post('/logs', { food_id: foodId, grams });
    return data;
  },

  async remove(id) {
    await axiosClient.delete(`/logs/${id}`);
  },
};

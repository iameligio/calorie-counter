import axiosClient from './axiosClient';

/**
 * Read-only dashboard and progress aggregates.
 */
export const statsService = {
  async dashboard(date) {
    const { data } = await axiosClient.get('/dashboard', {
      params: date ? { date } : {},
    });
    return data;
  },

  async progress(period = 'week') {
    const { data } = await axiosClient.get('/progress', { params: { period } });
    return data;
  },
};

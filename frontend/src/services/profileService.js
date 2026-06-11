import axiosClient from './axiosClient';

/**
 * User profile (biometrics + calorie-target inputs).
 */
export const profileService = {
  async update(payload) {
    const { data } = await axiosClient.put('/profile', payload);
    return data;
  },
};

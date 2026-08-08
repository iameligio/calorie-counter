import axios from 'axios';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://myfitnesspal.test/api';

const axiosClient = axios.create({
  baseURL: apiBaseUrl,
  // Auth is a Bearer token, not a cookie. Requesting credentials would also
  // force the API to send Access-Control-Allow-Credentials, which it no
  // longer does.
  withCredentials: false,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

// Request interceptor to attach Bearer token
axiosClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// On any 401, drop the stale token. Routing back to /login is handled by the
// router guards in App.jsx, which react to the cleared auth state.
axiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
    }
    return Promise.reject(error);
  }
);

export default axiosClient;

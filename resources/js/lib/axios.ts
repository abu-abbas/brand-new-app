import axios, { type AxiosRequestConfig } from 'axios';

export const axiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
});

axiosInstance.interceptors.request.use((config) => {
  if (!config.headers['X-Request-Id']) {
    const uuid = crypto.randomUUID();
    config.headers['X-Request-Id'] = uuid;
  }
  return config;
});

axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    // 422 validation error diserahkan ke penangan form / caller
    if (error.response?.status === 422) {
      return Promise.reject(error);
    }
    // Error lain (401, 403, 409, 500) diproses atau diteruskan
    return Promise.reject(error);
  },
);

export const customAxiosInstance = <T>(
  config: AxiosRequestConfig,
  options?: AxiosRequestConfig,
): Promise<T> => {
  return axiosInstance({ ...config, ...options }).then(({ data }) => data);
};

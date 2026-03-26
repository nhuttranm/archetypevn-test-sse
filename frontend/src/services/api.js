import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Auth API
export const authApi = {
  login: async (credentials) => {
    // Correct URL for the CSRF cookie initialization endpoint
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    return api.post('/login', credentials);
  },
  logout: () => api.post('/logout'),
  getUser: () => api.get('/user'),
};

// Purchase Orders API
export const purchaseOrderApi = {
  list: (params) => api.get('/purchase-orders', { params }),
  get: (id) => api.get(`/purchase-orders/${id}`),
  create: (data) => api.post('/purchase-orders', data),
  update: (id, data) => api.put(`/purchase-orders/${id}`, data),
  delete: (id) => api.delete(`/purchase-orders/${id}`),
  submit: (id) => api.post(`/purchase-orders/${id}/submit`),
  approve: (id, data) => api.post(`/purchase-orders/${id}/approve`, data),
  reject: (id, data) => api.post(`/purchase-orders/${id}/reject`, data),
  revise: (id) => api.post(`/purchase-orders/${id}/revise`),
  dashboard: () => api.get('/purchase-orders/dashboard'),
};

// Lookup API
export const lookupApi = {
  departments: () => api.get('/departments'),
  vendors: () => api.get('/vendors'),
};

// Audit Log API
export const auditLogApi = {
  list: (params) => api.get('/audit-logs', { params }),
  getByPo: (poId) => api.get(`/audit-logs/${poId}`),
};

// Approval Rules API
export const approvalRuleApi = {
  list: () => api.get('/approval-rules'),
  create: (data) => api.post('/approval-rules', data),
  update: (id, data) => api.put(`/approval-rules/${id}`, data),
  delete: (id) => api.delete(`/approval-rules/${id}`),
  chain: () => api.get('/approval-rules/chain'),
};

export default api;

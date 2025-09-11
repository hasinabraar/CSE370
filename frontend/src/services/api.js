import axios from 'axios';

// Create axios instance
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error) => {
    // Do not auto-logout on API business/validation failures
    if (error.response?.status === 401) {
      // Only logout if token is missing/expired and the endpoint is auth-protected
      const msg = error.response?.data?.error || error.response?.data?.message || '';
      const isAuthIssue = msg.toLowerCase().includes('token') || msg.toLowerCase().includes('unauthorized');
      if (isAuthIssue) {
        localStorage.removeItem('token');
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

// Authentication service
export const authService = {
  login: (email, password) => {
    return api.post('/auth/login', { email, password });
  },

  register: (userData) => {
    return api.post('/auth/register', userData);
  },

  getProfile: () => {
    return api.get('/auth/profile');
  },

  updateProfile: (profileData) => {
    return api.put('/auth/profile', profileData);
  },

  changePassword: (passwordData) => {
    return api.put('/auth/change-password', passwordData);
  },
};

// Accident service
export const accidentService = {
  getAccidents: (filters = {}) => {
    return api.get('/accidents', { params: filters });
  },

  getAccident: (id) => {
    return api.get(`/accidents/${id}`);
  },

  createAccident: (accidentData) => {
    return api.post('/accidents', accidentData);
  },

  updateAccident: (id, accidentData) => {
    return api.put(`/accidents/${id}`, accidentData);
  },

  getStatistics: (filters = {}) => {
    return api.get('/accidents/statistics', { params: filters });
  },
  reportAccident: (payload) => {
    return api.post('/reportAccident', payload);
  },
};

// Car service
export const carService = {
  getCars: () => {
    return api.get('/cars');
  },

  getCar: (id) => {
    return api.get(`/cars/${id}`);
  },

  registerCar: (carData) => {
    return api.post('/cars', carData);
  },

  updateCar: (id, carData) => {
    return api.put(`/cars/${id}`, carData);
  },

  deleteCar: (id) => {
    return api.delete(`/cars/${id}`);
  },

  updateSensorStatus: (id, status) => {
    return api.put(`/cars/${id}/sensor`, { sensor_status: status });
  },

  getCarAccidents: (carId, filters = {}) => {
    return api.get(`/cars/${carId}/accidents`, { params: filters });
  },
};

// Hospital service
export const hospitalService = {
  getHospitals: (filters = {}) => {
    return api.get('/hospitals', { params: filters });
  },

  getHospital: (id) => {
    return api.get(`/hospitals/${id}`);
  },

  updateAmbulanceAvailability: (id, available) => {
    return api.put(`/hospitals/${id}/ambulance`, { ambulance_available: available });
  },

  getNearbyHospitals: (lat, lng, radius = 50) => {
    return api.get('/hospitals/nearby', { params: { lat, lng, radius } });
  },

  getHospitalActivity: (id) => {
    return api.get(`/hospitals/${id}/activity`);
  },

  getHospitalNotifications: (id, filters = {}) => {
    return api.get(`/hospitals/${id}/notifications`, { params: filters });
  },

  markNotificationAsRead: (notificationId, hospitalId) => {
    return api.put(`/hospitals/${hospitalId}/notifications/${notificationId}/read`);
  },
};

// Police service
export const policeService = {
  // Police Alerts
  getAlerts: (filters = {}) => {
    return api.get('/police/alerts', { params: filters });
  },
  markAlertRead: (alertId, policeStationId) => {
    return api.put(`/police/alerts/${alertId}/read`, null, { params: { police_station_id: policeStationId } });
  },
  
  // Police Stations
  getStations: (filters = {}) => {
    return api.get('/police/stations', { params: filters });
  },
  getStation: (id) => {
    return api.get(`/police/stations/${id}`);
  },
  createStation: (stationData) => {
    return api.post('/police/stations', stationData);
  },
  updateStation: (id, stationData) => {
    return api.put(`/police/stations/${id}`, stationData);
  },
  deleteStation: (id) => {
    return api.delete(`/police/stations/${id}`);
  },
  getNearbyStations: (lat, lng, radius = 50) => {
    return api.get('/police/stations/nearby', { params: { lat, lng, radius } });
  },
};

// Admin service
export const adminService = {
  createHospital: (data) => api.post('/admin/hospitals', data),
  updateHospital: (id, data) => api.put(`/admin/hospitals/${id}`, data),
  deleteHospital: (id) => api.delete(`/admin/hospitals/${id}`),
  createPoliceStation: (data) => api.post('/admin/police-stations', data),
  updatePoliceStation: (id, data) => api.put(`/admin/police-stations/${id}`, data),
  deletePoliceStation: (id) => api.delete(`/admin/police-stations/${id}`),
};

export default api;

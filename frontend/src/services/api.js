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
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
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

export default api;

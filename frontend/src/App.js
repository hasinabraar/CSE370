import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';
import Layout from './components/Layout';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import Accidents from './pages/Accidents';
import Cars from './pages/Cars';
import Hospitals from './pages/Hospitals';
import Police from './pages/Police';
import Reports from './pages/Reports';
import Profile from './pages/Profile';
import Admin from './pages/Admin';

function App() {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div className="App">
      <Routes>
        {/* Public routes */}
        <Route path="/login" element={!user ? <Login /> : <Navigate to="/dashboard" />} />
        <Route path="/register" element={!user ? <Register /> : <Navigate to="/dashboard" />} />
        
        {/* Protected routes */}
        <Route path="/" element={user ? <Layout /> : <Navigate to="/login" />}>
          <Route index element={<Navigate to="/dashboard" />} />
          <Route path="dashboard" element={<Dashboard />} />
          <Route path="accidents" element={<Accidents />} />
          <Route path="cars" element={<Cars />} />
          <Route path="hospitals" element={<Hospitals />} />
          <Route path="police" element={<Police />} />
          <Route path="reports" element={<Reports />} />
          <Route path="profile" element={<Profile />} />
          {user?.role === 'admin' && <Route path="admin" element={<Admin />} />}
        </Route>
        
        {/* Catch all route */}
        <Route path="*" element={<Navigate to={user ? "/dashboard" : "/login"} />} />
      </Routes>
    </div>
  );
}

export default App;

import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { accidentService } from '../services/api';
import { 
  FiAlertTriangle, 
  FiCheckCircle, 
  FiClock, 
  FiTruck,
  FiMapPin,
  FiTrendingUp
} from 'react-icons/fi';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar, PieChart, Pie, Cell } from 'recharts';
import toast from 'react-hot-toast';

const Dashboard = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [recentAccidents, setRecentAccidents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      
      // Get statistics
      const statsResponse = await accidentService.getStatistics();
      if (statsResponse.success) {
        setStats(statsResponse.data);
      }

      // Get recent accidents
      const accidentsResponse = await accidentService.getAccidents({
        limit: 5,
        sort_by: 'accident_time',
        sort_order: 'DESC'
      });
      if (accidentsResponse.success) {
        setRecentAccidents(accidentsResponse.data);
      }
    } catch (error) {
      toast.error('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  const getSeverityColor = (severity) => {
    const colors = {
      low: 'text-success-600',
      medium: 'text-warning-600',
      high: 'text-orange-600',
      critical: 'text-danger-600'
    };
    return colors[severity] || 'text-gray-600';
  };

  const getStatusColor = (status) => {
    const colors = {
      pending: 'text-warning-600',
      in_progress: 'text-primary-600',
      resolved: 'text-success-600',
      cancelled: 'text-gray-600'
    };
    return colors[status] || 'text-gray-600';
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-600">Welcome back, {user?.name}!</p>
      </div>

      {/* Statistics Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-2 bg-primary-100 rounded-lg">
                <FiAlertTriangle className="h-6 w-6 text-primary-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total Accidents</p>
                <p className="text-2xl font-bold text-gray-900">{stats.total_accidents}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-2 bg-warning-100 rounded-lg">
                <FiClock className="h-6 w-6 text-warning-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Pending</p>
                <p className="text-2xl font-bold text-gray-900">{stats.pending}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-2 bg-success-100 rounded-lg">
                <FiCheckCircle className="h-6 w-6 text-success-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Resolved</p>
                <p className="text-2xl font-bold text-gray-900">{stats.resolved}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-2 bg-orange-100 rounded-lg">
                <FiTrendingUp className="h-6 w-6 text-orange-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">In Progress</p>
                <p className="text-2xl font-bold text-gray-900">{stats.in_progress}</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Charts and Recent Accidents */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Severity Distribution */}
        {stats && (
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Accident Severity Distribution</h3>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={[
                      { name: 'Low', value: stats.low_severity, color: '#22c55e' },
                      { name: 'Medium', value: stats.medium_severity, color: '#f59e0b' },
                      { name: 'High', value: stats.high_severity, color: '#f97316' },
                      { name: 'Critical', value: stats.critical_severity, color: '#ef4444' }
                    ]}
                    cx="50%"
                    cy="50%"
                    outerRadius={80}
                    dataKey="value"
                    label={({ name, value }) => `${name}: ${value}`}
                  >
                    {[
                      { name: 'Low', value: stats.low_severity, color: '#22c55e' },
                      { name: 'Medium', value: stats.medium_severity, color: '#f59e0b' },
                      { name: 'High', value: stats.high_severity, color: '#f97316' },
                      { name: 'Critical', value: stats.critical_severity, color: '#ef4444' }
                    ].map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>
        )}

        {/* Recent Accidents */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Accidents</h3>
          <div className="space-y-4">
            {recentAccidents.length > 0 ? (
              recentAccidents.map((accident) => (
                <div key={accident.id} className="border-l-4 border-primary-500 pl-4 py-2">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-gray-900">
                        {accident.plate_number}
                      </p>
                      <p className="text-xs text-gray-500">
                        {accident.owner_name} • {formatDate(accident.accident_time)}
                      </p>
                    </div>
                    <div className="flex items-center space-x-2">
                      <span className={`text-xs font-medium ${getSeverityColor(accident.severity)}`}>
                        {accident.severity}
                      </span>
                      <span className={`text-xs font-medium ${getStatusColor(accident.status)}`}>
                        {accident.status}
                      </span>
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <p className="text-gray-500 text-center py-4">No recent accidents</p>
            )}
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <button className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <FiAlertTriangle className="h-6 w-6 text-primary-600 mr-3" />
            <div className="text-left">
              <p className="font-medium text-gray-900">Report Accident</p>
              <p className="text-sm text-gray-500">Log a new accident</p>
            </div>
          </button>
          
          <button className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <FiTruck className="h-6 w-6 text-primary-600 mr-3" />
            <div className="text-left">
              <p className="font-medium text-gray-900">Manage Cars</p>
              <p className="text-sm text-gray-500">View registered vehicles</p>
            </div>
          </button>
          
          <button className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <FiMapPin className="h-6 w-6 text-primary-600 mr-3" />
            <div className="text-left">
              <p className="font-medium text-gray-900">Hospitals</p>
              <p className="text-sm text-gray-500">View hospital network</p>
            </div>
          </button>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;

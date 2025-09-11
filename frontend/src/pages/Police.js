import React, { useEffect, useMemo, useState } from 'react';
import { policeService } from '../services/api';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import PoliceStationModal from '../components/PoliceStationModal';
import PoliceStationCard from '../components/PoliceStationCard';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
});

const Police = () => {
  const [activeTab, setActiveTab] = useState('alerts');
  const [alerts, setAlerts] = useState([]);
  const [stations, setStations] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [editingStation, setEditingStation] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filters, setFilters] = useState({});
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const [stationForm, setStationForm] = useState({
    name: '',
    jurisdiction: '',
    latitude: '',
    longitude: '',
    address: '',
    phone: ''
  });

  useEffect(() => {
    if (activeTab === 'alerts') {
      loadAlerts();
      const id = setInterval(loadAlerts, 5000);
      return () => clearInterval(id);
    } else if (activeTab === 'stations') {
      loadStations();
    }
  }, [activeTab, filters]);

  const showMessage = (message, type = 'success') => {
    if (type === 'success') {
      setSuccess(message);
      setError('');
    } else {
      setError(message);
      setSuccess('');
    }
    setTimeout(() => {
      setSuccess('');
      setError('');
    }, 5000);
  };

  const loadAlerts = async () => {
    setLoading(true);
    try {
      const res = await policeService.getAlerts(filters);
      if (res.success) {
        setAlerts(res.data);
      } else {
        showMessage(res.message || 'Failed to load alerts', 'error');
      }
    } catch (error) {
      showMessage('Failed to load alerts: ' + error.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const loadStations = async () => {
    setLoading(true);
    try {
      const res = await policeService.getStations({ search: searchTerm, ...filters });
      if (res.success) {
        setStations(res.data);
      } else {
        showMessage(res.message || 'Failed to load stations', 'error');
      }
    } catch (error) {
      showMessage('Failed to load stations: ' + error.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleMarkAlertRead = async (alertId, policeStationId) => {
    try {
      const res = await policeService.markAlertRead(alertId, policeStationId);
      if (res.success) {
        showMessage('Alert marked as read');
        loadAlerts();
      } else {
        showMessage(res.message || 'Failed to mark alert as read', 'error');
      }
    } catch (error) {
      showMessage('Failed to mark alert as read: ' + error.message, 'error');
    }
  };

  const handleCreateStation = async (e) => {
    e.preventDefault();
    try {
      const res = await policeService.createStation(stationForm);
      if (res.success) {
        setShowModal(false);
        setStationForm({ name: '', jurisdiction: '', latitude: '', longitude: '', address: '', phone: '' });
        showMessage('Police station created successfully');
        loadStations();
      } else {
        showMessage(res.message || 'Failed to create police station', 'error');
      }
    } catch (error) {
      showMessage('Failed to create station: ' + error.message, 'error');
    }
  };

  const handleUpdateStation = async (e) => {
    e.preventDefault();
    try {
      const res = await policeService.updateStation(editingStation.id, stationForm);
      if (res.success) {
        setShowModal(false);
        setEditingStation(null);
        setStationForm({ name: '', jurisdiction: '', latitude: '', longitude: '', address: '', phone: '' });
        showMessage('Police station updated successfully');
        loadStations();
      } else {
        showMessage(res.message || 'Failed to update police station', 'error');
      }
    } catch (error) {
      showMessage('Failed to update station: ' + error.message, 'error');
    }
  };

  const handleDeleteStation = async (id) => {
    if (!window.confirm('Are you sure you want to delete this police station?')) return;
    
    try {
      const res = await policeService.deleteStation(id);
      if (res.success) {
        showMessage('Police station deleted successfully');
        loadStations();
      } else {
        showMessage(res.message || 'Failed to delete police station', 'error');
      }
    } catch (error) {
      showMessage('Failed to delete station: ' + error.message, 'error');
    }
  };

  const openEditModal = (station) => {
    setEditingStation(station);
    setStationForm({
      name: station.name,
      jurisdiction: station.jurisdiction,
      latitude: station.latitude,
      longitude: station.longitude,
      address: station.address,
      phone: station.phone
    });
    setShowModal(true);
  };

  const openCreateModal = () => {
    setEditingStation(null);
    setStationForm({ name: '', jurisdiction: '', latitude: '', longitude: '', address: '', phone: '' });
    setShowModal(true);
  };

  const center = useMemo(() => {
    if (activeTab === 'alerts' && alerts.length > 0) {
      return [alerts[0].location_lat, alerts[0].location_lng];
    }
    if (activeTab === 'stations' && stations.length > 0) {
      return [parseFloat(stations[0].latitude), parseFloat(stations[0].longitude)];
    }
    return [40.7128, -74.0060]; // Default to NYC
  }, [alerts, stations, activeTab]);

  const filteredStations = stations.filter(station => 
    station.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    station.jurisdiction.toLowerCase().includes(searchTerm.toLowerCase()) ||
    station.address.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const alertStats = useMemo(() => {
    const total = alerts.length;
    const unread = alerts.filter(alert => alert.status !== 'read').length;
    const read = total - unread;
    return { total, unread, read };
  }, [alerts]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Police Management</h1>
        <p className="text-gray-600">Manage police stations and view accident alerts</p>
      </div>

      {/* Status Messages */}
      {success && (
        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {success}
        </div>
      )}
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      {/* Tab Navigation */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          <button
            onClick={() => setActiveTab('alerts')}
            className={`py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'alerts'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Alerts & Dashboard
            {alertStats.unread > 0 && (
              <span className="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                {alertStats.unread}
              </span>
            )}
          </button>
          <button
            onClick={() => setActiveTab('stations')}
            className={`py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'stations'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Police Stations ({stations.length})
          </button>
        </nav>
      </div>

      {/* Alerts Tab */}
      {activeTab === 'alerts' && (
        <div className="space-y-6">
          {/* Alert Statistics */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white p-4 rounded-lg shadow">
              <div className="text-center">
                <div className="text-2xl font-bold text-blue-600">{alertStats.total}</div>
                <div className="text-sm text-gray-600">Total Alerts</div>
              </div>
            </div>
            <div className="bg-white p-4 rounded-lg shadow">
              <div className="text-center">
                <div className="text-2xl font-bold text-red-600">{alertStats.unread}</div>
                <div className="text-sm text-gray-600">Unread Alerts</div>
              </div>
            </div>
            <div className="bg-white p-4 rounded-lg shadow">
              <div className="text-center">
                <div className="text-2xl font-bold text-green-600">{alertStats.read}</div>
                <div className="text-sm text-gray-600">Read Alerts</div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white rounded-lg shadow overflow-hidden">
              <div className="p-4 border-b">
                <h2 className="text-lg font-semibold">Alert Locations</h2>
              </div>
              <div className="h-96">
                <MapContainer center={center} zoom={12} style={{ height: '100%', width: '100%' }}>
                  <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" attribution="&copy; OpenStreetMap contributors" />
                  {alerts.map(a => (
                    <Marker key={a.id} position={[a.location_lat, a.location_lng]}>
                      <Popup>
                        <div className="text-sm">
                          <div className="font-semibold">Plate: {a.plate_number}</div>
                          <div>Severity: {a.severity}</div>
                          <div>Station: {a.police_station_name}</div>
                          <div>Status: <span className={a.status === 'read' ? 'text-green-600' : 'text-red-600'}>{a.status}</span></div>
                        </div>
                      </Popup>
                    </Marker>
                  ))}
                </MapContainer>
              </div>
            </div>

            <div className="bg-white rounded-lg shadow p-4">
              <h2 className="text-lg font-semibold mb-3">Recent Alerts</h2>
              <div className="space-y-3 max-h-96 overflow-auto">
                {alerts.map(a => (
                  <div key={a.id} className={`border rounded p-3 ${a.status === 'read' ? 'bg-gray-50' : 'bg-yellow-50 border-yellow-200'}`}>
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <div className="font-medium">{a.message}</div>
                        <div className="text-sm text-gray-600 mt-1 space-y-1">
                          <div>Station: {a.police_station_name}</div>
                          <div>Plate: {a.plate_number} • Severity: <span className={`font-medium ${a.severity === 'high' ? 'text-red-600' : a.severity === 'medium' ? 'text-yellow-600' : 'text-green-600'}`}>{a.severity}</span></div>
                          <div>Owner: {a.owner_name}</div>
                          <div className="text-xs text-gray-500">{new Date(a.sent_time).toLocaleString()}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
                {alerts.length === 0 && (
                  <div className="text-gray-500 text-center py-8">
                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m8-6v6m4-3H8" />
                    </svg>
                    <p className="mt-2">No alerts available</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Stations Tab */}
      {activeTab === 'stations' && (
        <div className="space-y-6">
          {/* Controls */}
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div className="flex space-x-4">
              <input
                type="text"
                placeholder="Search stations..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <button
              onClick={openCreateModal}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            >
              Add Police Station
            </button>
          </div>

          {/* Stations Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Stations Map */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
              <div className="p-4 border-b">
                <h2 className="text-lg font-semibold">Station Locations</h2>
              </div>
              <div className="h-96">
                <MapContainer center={center} zoom={10} style={{ height: '100%', width: '100%' }}>
                  <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" attribution="&copy; OpenStreetMap contributors" />
                  {filteredStations.map(station => (
                    <Marker key={station.id} position={[parseFloat(station.latitude), parseFloat(station.longitude)]}>
                      <Popup>
                        <div className="text-sm">
                          <div className="font-semibold">{station.name}</div>
                          <div>Jurisdiction: {station.jurisdiction}</div>
                          <div>Phone: {station.phone}</div>
                          <div>Address: {station.address}</div>
                        </div>
                      </Popup>
                    </Marker>
                  ))}
                </MapContainer>
              </div>
            </div>

            {/* Stations List */}
            <div className="bg-white rounded-lg shadow">
              <div className="p-4 border-b">
                <h2 className="text-lg font-semibold">Police Stations ({filteredStations.length})</h2>
              </div>
              <div className="max-h-96 overflow-auto">
                {filteredStations.map(station => (
                  <PoliceStationCard
                    key={station.id}
                    station={station}
                    onEdit={openEditModal}
                    onDelete={handleDeleteStation}
                  />
                ))}
                {filteredStations.length === 0 && (
                  <div className="p-8 text-center text-gray-500">
                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0h3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p className="mt-2">
                      {searchTerm ? 'No stations match your search' : 'No police stations found'}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Station Modal */}
      <PoliceStationModal
        showModal={showModal}
        setShowModal={setShowModal}
        editingStation={editingStation}
        stationForm={stationForm}
        setStationForm={setStationForm}
        handleCreateStation={handleCreateStation}
        handleUpdateStation={handleUpdateStation}
      />

      {loading && (
        <div className="text-center text-gray-500 py-4">
          <svg className="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p className="mt-2">Loading...</p>
        </div>
      )}
    </div>
  );
};

export default Police;



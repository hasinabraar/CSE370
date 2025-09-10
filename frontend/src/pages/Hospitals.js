import React, { useEffect, useMemo, useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { hospitalService } from '../services/api';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix default icon paths in Leaflet
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
});

const Hospitals = () => {
  const { user } = useAuth();
  const [hospitals, setHospitals] = useState([]);
  const [alerts, setAlerts] = useState([]);
  const [selectedHospital, setSelectedHospital] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    load();
    const id = setInterval(() => loadAlerts(selectedHospital?.id), 5000);
    return () => clearInterval(id);
  }, [selectedHospital?.id]);

  const load = async () => {
    setLoading(true);
    try {
      const res = await hospitalService.getHospitals();
      if (res.success) {
        setHospitals(res.data);
        // If hospital user, auto-select matching hospital by name
        if (user?.role === 'hospital') {
          const match = res.data.find(h => h.name === user.name);
          if (match) {
            setSelectedHospital(match);
            await loadAlerts(match.id);
          }
        }
      }
    } finally {
      setLoading(false);
    }
  };

  const loadAlerts = async (hospitalId) => {
    if (!hospitalId) return;
    const res = await hospitalService.getHospitalNotifications(hospitalId, { status: 'sent' });
    if (res.success) setAlerts(res.data);
  };

  const center = useMemo(() => {
    if (selectedHospital) return [selectedHospital.latitude, selectedHospital.longitude];
    if (hospitals.length > 0) return [hospitals[0].latitude, hospitals[0].longitude];
    return [40.7128, -74.0060];
  }, [selectedHospital, hospitals]);

  const handleAction = async (accidentId, action) => {
    // action: 'dispatch' => in_progress, 'resolve' => resolved
    const status = action === 'dispatch' ? 'in_progress' : 'resolved';
    const resp = await fetch('/api/accidents/' + accidentId, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${localStorage.getItem('token')}` },
      body: JSON.stringify({ status, change_reason: action === 'dispatch' ? 'Ambulance dispatched' : 'Accident resolved' })
    }).then(r => r.json());
    if (resp.success) await loadAlerts(selectedHospital.id);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Hospital Dashboard</h1>
          <p className="text-gray-600">View alerts and manage responses</p>
        </div>
        <div>
          <select
            className="border rounded px-3 py-2"
            value={selectedHospital?.id || ''}
            onChange={(e) => {
              const h = hospitals.find(x => x.id === Number(e.target.value));
              setSelectedHospital(h);
              loadAlerts(h?.id);
            }}
          >
            <option value="">Select hospital</option>
            {hospitals.map(h => (
              <option key={h.id} value={h.id}>{h.name}</option>
            ))}
          </select>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="h-96">
            <MapContainer center={center} zoom={12} style={{ height: '100%', width: '100%' }}>
              <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" attribution="&copy; OpenStreetMap contributors" />
              {alerts.map(a => (
                <Marker key={a.id} position={[a.location_lat, a.location_lng]}>
                  <Popup>
                    <div className="text-sm">
                      <div className="font-semibold">Plate: {a.plate_number}</div>
                      <div>Severity: {a.severity}</div>
                      <div>Status: {a.accident_status}</div>
                    </div>
                  </Popup>
                </Marker>
              ))}
            </MapContainer>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-4">
          <h2 className="text-lg font-semibold mb-3">Accident Alerts</h2>
          <div className="space-y-3 max-h-96 overflow-auto">
            {alerts.length === 0 && (
              <div className="text-gray-500">No alerts</div>
            )}
            {alerts.map(a => (
              <div key={a.id} className="border rounded p-3 flex items-center justify-between">
                <div>
                  <div className="font-medium">{a.message}</div>
                  <div className="text-xs text-gray-500">Plate {a.plate_number} • Severity {a.severity}</div>
                </div>
                <div className="space-x-2">
                  <button className="px-3 py-1 text-white bg-blue-600 rounded" onClick={() => handleAction(a.accident_id, 'dispatch')}>Dispatch</button>
                  <button className="px-3 py-1 text-white bg-green-600 rounded" onClick={() => handleAction(a.accident_id, 'resolve')}>Resolve</button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
      {loading && (
        <div className="text-center text-gray-500">Loading...</div>
      )}
    </div>
  );
};

export default Hospitals;

import React, { useEffect, useMemo, useState } from 'react';
import { policeService } from '../services/api';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
});

const Police = () => {
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    load();
    const id = setInterval(load, 5000);
    return () => clearInterval(id);
  }, []);

  const load = async () => {
    setLoading(true);
    try {
      const res = await policeService.getAlerts();
      if (res.success) setAlerts(res.data);
    } finally {
      setLoading(false);
    }
  };

  const center = useMemo(() => {
    if (alerts.length > 0) return [alerts[0].location_lat, alerts[0].location_lng];
    return [40.7128, -74.0060];
  }, [alerts]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Police Dashboard</h1>
        <p className="text-gray-600">View accident alerts</p>
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
                    </div>
                  </Popup>
                </Marker>
              ))}
            </MapContainer>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-4">
          <h2 className="text-lg font-semibold mb-3">Alerts</h2>
          <div className="space-y-3 max-h-96 overflow-auto">
            {alerts.map(a => (
              <div key={a.id} className="border rounded p-3">
                <div className="font-medium">{a.message}</div>
                <div className="text-xs text-gray-500">Plate {a.plate_number} • Severity {a.severity} • {new Date(a.sent_time).toLocaleString()}</div>
              </div>
            ))}
            {alerts.length === 0 && <div className="text-gray-500">No alerts</div>}
          </div>
        </div>
      </div>

      {loading && <div className="text-center text-gray-500">Loading...</div>}
    </div>
  );
};

export default Police;



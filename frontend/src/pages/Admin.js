import React, { useEffect, useState } from 'react';
import { adminService, hospitalService } from '../services/api';

const Admin = () => {
  const [hospitals, setHospitals] = useState([]);
  const [form, setForm] = useState({ name: '', latitude: '', longitude: '', address: '', phone: '', emergency_contact: '' });

  const loadHospitals = async () => {
    const res = await hospitalService.getHospitals();
    if (res.success) setHospitals(res.data);
  };

  useEffect(() => { loadHospitals(); }, []);

  const createHospital = async (e) => {
    e.preventDefault();
    const res = await adminService.createHospital({ ...form, latitude: Number(form.latitude), longitude: Number(form.longitude) });
    if (res.success) { setForm({ name: '', latitude: '', longitude: '', address: '', phone: '', emergency_contact: '' }); loadHospitals(); }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Admin Panel</h1>
        <p className="text-gray-600">Manage hospitals and police stations</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-3">Create Hospital</h2>
          <form onSubmit={createHospital} className="space-y-3">
            <input className="w-full border rounded px-3 py-2" placeholder="Name" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required />
            <div className="grid grid-cols-2 gap-3">
              <input className="w-full border rounded px-3 py-2" placeholder="Latitude" value={form.latitude} onChange={e => setForm({ ...form, latitude: e.target.value })} required />
              <input className="w-full border rounded px-3 py-2" placeholder="Longitude" value={form.longitude} onChange={e => setForm({ ...form, longitude: e.target.value })} required />
            </div>
            <input className="w-full border rounded px-3 py-2" placeholder="Address" value={form.address} onChange={e => setForm({ ...form, address: e.target.value })} />
            <div className="grid grid-cols-2 gap-3">
              <input className="w-full border rounded px-3 py-2" placeholder="Phone" value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })} />
              <input className="w-full border rounded px-3 py-2" placeholder="Emergency Contact" value={form.emergency_contact} onChange={e => setForm({ ...form, emergency_contact: e.target.value })} />
            </div>
            <button className="px-4 py-2 bg-primary-600 text-white rounded">Create</button>
          </form>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-3">Hospitals</h2>
          <div className="space-y-2 max-h-96 overflow-auto">
            {hospitals.map(h => (
              <div key={h.id} className="border rounded p-3 flex items-center justify-between">
                <div>
                  <div className="font-medium">{h.name}</div>
                  <div className="text-xs text-gray-500">{h.latitude}, {h.longitude}</div>
                </div>
                <button className="px-3 py-1 bg-red-600 text-white rounded" onClick={async () => { await adminService.deleteHospital(h.id); loadHospitals(); }}>Delete</button>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Admin;


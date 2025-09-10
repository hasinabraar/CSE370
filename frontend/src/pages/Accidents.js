import React, { useState } from 'react';
import { accidentService, carService } from '../services/api';
import toast from 'react-hot-toast';

const Accidents = () => {
  const [form, setForm] = useState({ car_id: '', location_lat: '', location_lng: '', severity: 'medium', description: '' });
  const [submitting, setSubmitting] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await accidentService.reportAccident(form);
      if (res.success) {
        toast.success('Accident reported');
        setForm({ car_id: '', location_lat: '', location_lng: '', severity: 'medium', description: '' });
      } else {
        toast.error(res.message || 'Failed to report');
      }
    } catch (e1) {
      toast.error('Failed to report');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Accidents</h1>
        <p className="text-gray-600">Report a simulated accident</p>
      </div>

      <div className="bg-white rounded-lg shadow p-6 max-w-xl">
        <form onSubmit={submit} className="space-y-4">
          <div>
            <label className="block text-sm text-gray-700 mb-1">Car ID</label>
            <input className="w-full border rounded px-3 py-2" value={form.car_id} onChange={e => setForm({ ...form, car_id: Number(e.target.value) })} placeholder="1" required />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm text-gray-700 mb-1">Latitude</label>
              <input className="w-full border rounded px-3 py-2" value={form.location_lat} onChange={e => setForm({ ...form, location_lat: Number(e.target.value) })} placeholder="40.7128" required />
            </div>
            <div>
              <label className="block text-sm text-gray-700 mb-1">Longitude</label>
              <input className="w-full border rounded px-3 py-2" value={form.location_lng} onChange={e => setForm({ ...form, location_lng: Number(e.target.value) })} placeholder="-74.0060" required />
            </div>
          </div>
          <div>
            <label className="block text-sm text-gray-700 mb-1">Severity</label>
            <select className="w-full border rounded px-3 py-2" value={form.severity} onChange={e => setForm({ ...form, severity: e.target.value })}>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          </div>
          <div>
            <label className="block text-sm text-gray-700 mb-1">Description</label>
            <textarea className="w-full border rounded px-3 py-2" value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} placeholder="Brief details" />
          </div>
          <button disabled={submitting} className="px-4 py-2 text-white bg-primary-600 rounded">
            {submitting ? 'Submitting...' : 'Trigger Accident'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default Accidents;

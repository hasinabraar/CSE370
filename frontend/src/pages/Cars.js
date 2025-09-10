import React, { useEffect, useState } from 'react';
import { carService } from '../services/api';
import toast from 'react-hot-toast';

const Cars = () => {
  const [cars, setCars] = useState([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ plate_number: '', model: '', year: '' });
  const [editing, setEditing] = useState(null);
  const [accidents, setAccidents] = useState([]);
  const [selectedCar, setSelectedCar] = useState(null);

  const load = async () => {
    setLoading(true);
    try {
      const res = await carService.getCars();
      if (res.success) setCars(res.data);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, []);

  const createCar = async (e) => {
    e.preventDefault();
    const res = await carService.registerCar({ ...form, year: Number(form.year) });
    if (res.success) {
      toast.success('Car registered');
      setForm({ plate_number: '', model: '', year: '' });
      load();
    } else {
      toast.error(res.message || 'Failed to register');
    }
  };

  const updateCar = async (e) => {
    e.preventDefault();
    const res = await carService.updateCar(editing.id, { ...form, year: Number(form.year) });
    if (res.success) {
      toast.success('Car updated');
      setEditing(null);
      setForm({ plate_number: '', model: '', year: '' });
      load();
    } else {
      toast.error(res.message || 'Failed to update');
    }
  };

  const deleteCar = async (id) => {
    const res = await carService.deleteCar(id);
    if (res.success) {
      toast.success('Car deleted');
      if (selectedCar?.id === id) { setSelectedCar(null); setAccidents([]); }
      load();
    } else {
      toast.error(res.message || 'Failed to delete');
    }
  };

  const loadAccidents = async (carId) => {
    const res = await carService.getCarAccidents(carId);
    if (res.success) setAccidents(res.data);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Cars</h1>
        <p className="text-gray-600">Manage your registered vehicles</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-3">{editing ? 'Edit Car' : 'Register Car'}</h2>
          <form onSubmit={editing ? updateCar : createCar} className="space-y-3">
            {editing && (
              <div>
                <label className="block text-sm text-gray-700 mb-1">Car ID</label>
                <input className="w-full border rounded px-3 py-2 bg-gray-50" value={editing.id} readOnly />
              </div>
            )}
            <input className="w-full border rounded px-3 py-2" placeholder="Plate Number" value={form.plate_number} onChange={e => setForm({ ...form, plate_number: e.target.value })} required />
            <div className="grid grid-cols-2 gap-3">
              <input className="w-full border rounded px-3 py-2" placeholder="Model" value={form.model} onChange={e => setForm({ ...form, model: e.target.value })} required />
              <input className="w-full border rounded px-3 py-2" placeholder="Year" value={form.year} onChange={e => setForm({ ...form, year: e.target.value })} required />
            </div>
            <div className="space-x-2">
              <button className="px-4 py-2 bg-primary-600 text-white rounded" type="submit">{editing ? 'Update' : 'Create'}</button>
              {editing && (
                <button type="button" className="px-4 py-2 border rounded" onClick={() => { setEditing(null); setForm({ plate_number: '', model: '', year: '' }); }}>Cancel</button>
              )}
            </div>
          </form>

          <h2 className="text-lg font-semibold mt-8 mb-3">Your Cars</h2>
          <div className="space-y-2 max-h-96 overflow-auto">
            {cars.map(c => (
              <div key={c.id} className="border rounded p-3 flex items-center justify-between">
                <div>
                  <div className="font-medium">{c.plate_number} • {c.model} • {c.year}</div>
                  <div className="text-xs text-gray-500">ID: {c.id} • Sensor: {c.sensor_status}</div>
                </div>
                <div className="space-x-2">
                  <button className="px-3 py-1 border rounded" onClick={() => navigator.clipboard.writeText(String(c.id))}>Copy ID</button>
                  <button className="px-3 py-1 border rounded" onClick={() => { setEditing(c); setForm({ plate_number: c.plate_number, model: c.model, year: String(c.year) }); }}>Edit</button>
                  <button className="px-3 py-1 bg-red-600 text-white rounded" onClick={() => deleteCar(c.id)}>Delete</button>
                  <button className="px-3 py-1 bg-gray-800 text-white rounded" onClick={() => { setSelectedCar(c); loadAccidents(c.id); }}>Accidents</button>
                </div>
              </div>
            ))}
            {cars.length === 0 && <div className="text-gray-500">No cars yet</div>}
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold mb-3">{selectedCar ? `Accidents for ${selectedCar.plate_number}` : 'Accidents'}</h2>
          <div className="space-y-2 max-h-96 overflow-auto">
            {accidents.map(a => (
              <div key={a.id} className="border rounded p-3 flex items-center justify-between">
                <div>
                  <div className="font-medium">{a.accident_time} • {a.severity} • {a.status}</div>
                  <div className="text-xs text-gray-500">Nearest hospital: {a.hospital_name || 'N/A'}</div>
                </div>
              </div>
            ))}
            {selectedCar && accidents.length === 0 && <div className="text-gray-500">No accidents for this car</div>}
            {!selectedCar && <div className="text-gray-500">Select a car to view accidents</div>}
          </div>
        </div>
      </div>

      {loading && <div className="text-center text-gray-500">Loading...</div>}
    </div>
  );
};

export default Cars;

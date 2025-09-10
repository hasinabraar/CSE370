import React, { useEffect, useState } from 'react';
import { accidentService } from '../services/api';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';

const Reports = () => {
  const [stats, setStats] = useState(null);
  const [daily, setDaily] = useState([]);

  useEffect(() => {
    (async () => {
      const res = await accidentService.getStatistics();
      if (res.success) {
        setStats(res.data);
        setDaily((res.data.daily_stats || []).map(x => ({ day: x.accident_date, total: x.total_accidents })));
      }
    })();
  }, []);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Accident Statistics</h1>
        <p className="text-gray-600">Daily counts and summaries</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-medium mb-3">Accidents per day</h3>
          <div className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={daily}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="day" hide />
                <YAxis />
                <Tooltip />
                <Bar dataKey="total" fill="#3b82f6" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {stats && (
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-medium mb-3">Summary</h3>
            <ul className="space-y-1 text-gray-800">
              <li>Total: {stats.total_accidents}</li>
              <li>Low: {stats.low_severity}, Medium: {stats.medium_severity}, High: {stats.high_severity}, Critical: {stats.critical_severity}</li>
              <li>Pending: {stats.pending}, In Progress: {stats.in_progress}, Resolved: {stats.resolved}</li>
            </ul>
          </div>
        )}
      </div>
    </div>
  );
};

export default Reports;

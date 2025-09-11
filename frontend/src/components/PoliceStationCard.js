import React from 'react';

const PoliceStationCard = ({ station, onEdit, onDelete }) => {
  return (
    <div className="p-4 border-b hover:bg-gray-50 transition-colors">
      <div className="flex justify-between items-start">
        <div className="flex-1">
          <h3 className="font-medium text-gray-900 text-lg">{station.name}</h3>
          <div className="mt-2 space-y-1 text-sm text-gray-600">
            <p>
              <span className="font-medium">Jurisdiction:</span> {station.jurisdiction}
            </p>
            <p>
              <span className="font-medium">Phone:</span> {station.phone}
            </p>
            <p>
              <span className="font-medium">Address:</span> {station.address}
            </p>
            <p className="text-xs text-gray-500">
              <span className="font-medium">Coordinates:</span> {station.latitude}, {station.longitude}
            </p>
            {station.created_at && (
              <p className="text-xs text-gray-400">
                Created: {new Date(station.created_at).toLocaleDateString()}
              </p>
            )}
          </div>
        </div>
        <div className="flex space-x-2 ml-4">
          <button
            onClick={() => onEdit(station)}
            className="px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600 transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-500"
          >
            Edit
          </button>
          <button
            onClick={() => onDelete(station.id)}
            className="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  );
};

export default PoliceStationCard;

import React, { useState, useEffect } from 'react';
import { authService } from '../services/api';
import toast from 'react-hot-toast';

const Profile = () => {
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [editMode, setEditMode] = useState(false);
  const [showPasswordChange, setShowPasswordChange] = useState(false);
  
  // Profile form state
  const [profileForm, setProfileForm] = useState({
    name: '',
    phone: ''
  });
  
  // Password change form state
  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    new_password: '',
    confirm_password: ''
  });
  
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      setLoading(true);
      const res = await authService.getProfile();
      if (res.success) {
        setProfile(res.data);
        setProfileForm({
          name: res.data.name || '',
          phone: res.data.phone || ''
        });
      } else {
        toast.error(res.message || 'Failed to load profile');
      }
    } catch (error) {
      toast.error('Failed to load profile');
      console.error('Profile load error:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleProfileUpdate = async (e) => {
    e.preventDefault();
    if (!profileForm.name.trim()) {
      toast.error('Name is required');
      return;
    }

    try {
      setSubmitting(true);
      const res = await authService.updateProfile(profileForm);
      if (res.success) {
        setProfile(res.data);
        setEditMode(false);
        toast.success('Profile updated successfully');
      } else {
        toast.error(res.message || 'Failed to update profile');
      }
    } catch (error) {
      toast.error('Failed to update profile');
      console.error('Profile update error:', error);
    } finally {
      setSubmitting(false);
    }
  };

  const handlePasswordChange = async (e) => {
    e.preventDefault();
    
    if (!passwordForm.current_password || !passwordForm.new_password) {
      toast.error('All password fields are required');
      return;
    }
    
    if (passwordForm.new_password !== passwordForm.confirm_password) {
      toast.error('New passwords do not match');
      return;
    }
    
    if (passwordForm.new_password.length < 6) {
      toast.error('New password must be at least 6 characters long');
      return;
    }

    try {
      setSubmitting(true);
      const res = await authService.changePassword({
        current_password: passwordForm.current_password,
        new_password: passwordForm.new_password
      });
      
      if (res.success) {
        setPasswordForm({
          current_password: '',
          new_password: '',
          confirm_password: ''
        });
        setShowPasswordChange(false);
        toast.success('Password changed successfully');
      } else {
        toast.error(res.message || 'Failed to change password');
      }
    } catch (error) {
      toast.error('Failed to change password');
      console.error('Password change error:', error);
    } finally {
      setSubmitting(false);
    }
  };

  const cancelEdit = () => {
    setProfileForm({
      name: profile?.name || '',
      phone: profile?.phone || ''
    });
    setEditMode(false);
  };

  const cancelPasswordChange = () => {
    setPasswordForm({
      current_password: '',
      new_password: '',
      confirm_password: ''
    });
    setShowPasswordChange(false);
  };

  if (loading) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Profile</h1>
          <p className="text-gray-600">Manage your account settings</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <div className="animate-pulse">
            <div className="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
            <div className="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
            <div className="h-4 bg-gray-200 rounded w-1/3"></div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Profile</h1>
        <p className="text-gray-600">Manage your account settings</p>
      </div>

      {/* Profile Information Card */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex justify-between items-start mb-6">
          <h2 className="text-lg font-semibold text-gray-900">Profile Information</h2>
          {!editMode && (
            <button
              onClick={() => setEditMode(true)}
              className="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              Edit Profile
            </button>
          )}
        </div>

        {editMode ? (
          <form onSubmit={handleProfileUpdate} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Full Name *
              </label>
              <input
                type="text"
                value={profileForm.name}
                onChange={(e) => setProfileForm({ ...profileForm, name: e.target.value })}
                className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter your full name"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Phone Number
              </label>
              <input
                type="tel"
                value={profileForm.phone}
                onChange={(e) => setProfileForm({ ...profileForm, phone: e.target.value })}
                className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter your phone number"
              />
            </div>

            <div className="flex space-x-3 pt-4">
              <button
                type="submit"
                disabled={submitting}
                className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {submitting ? 'Saving...' : 'Save Changes'}
              </button>
              <button
                type="button"
                onClick={cancelEdit}
                disabled={submitting}
                className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        ) : (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700">Full Name</label>
              <p className="mt-1 text-sm text-gray-900">{profile?.name || 'Not provided'}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700">Email</label>
              <p className="mt-1 text-sm text-gray-900">{profile?.email}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700">Phone Number</label>
              <p className="mt-1 text-sm text-gray-900">{profile?.phone || 'Not provided'}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700">Role</label>
              <p className="mt-1 text-sm text-gray-900 capitalize">{profile?.role}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700">Member Since</label>
              <p className="mt-1 text-sm text-gray-900">
                {profile?.created_at ? new Date(profile.created_at).toLocaleDateString() : 'Unknown'}
              </p>
            </div>
          </div>
        )}
      </div>

      {/* Security Settings Card */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex justify-between items-start mb-6">
          <div>
            <h2 className="text-lg font-semibold text-gray-900">Security Settings</h2>
            <p className="text-sm text-gray-600">Manage your account security</p>
          </div>
          {!showPasswordChange && (
            <button
              onClick={() => setShowPasswordChange(true)}
              className="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
            >
              Change Password
            </button>
          )}
        </div>

        {showPasswordChange ? (
          <form onSubmit={handlePasswordChange} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Current Password *
              </label>
              <input
                type="password"
                value={passwordForm.current_password}
                onChange={(e) => setPasswordForm({ ...passwordForm, current_password: e.target.value })}
                className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter current password"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                New Password *
              </label>
              <input
                type="password"
                value={passwordForm.new_password}
                onChange={(e) => setPasswordForm({ ...passwordForm, new_password: e.target.value })}
                className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter new password (min 6 characters)"
                required
                minLength={6}
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Confirm New Password *
              </label>
              <input
                type="password"
                value={passwordForm.confirm_password}
                onChange={(e) => setPasswordForm({ ...passwordForm, confirm_password: e.target.value })}
                className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Confirm new password"
                required
              />
            </div>

            <div className="flex space-x-3 pt-4">
              <button
                type="submit"
                disabled={submitting}
                className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {submitting ? 'Changing...' : 'Change Password'}
              </button>
              <button
                type="button"
                onClick={cancelPasswordChange}
                disabled={submitting}
                className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        ) : (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700">Password</label>
              <p className="mt-1 text-sm text-gray-900">••••••••••••</p>
            </div>
            <p className="text-sm text-gray-600">
              Keep your account secure by using a strong password and changing it regularly.
            </p>
          </div>
        )}
      </div>

      {/* Account Information Card */}
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Account Information</h2>
        <div className="space-y-4">
          <div className="flex justify-between items-center py-3 border-b border-gray-200">
            <div>
              <p className="text-sm font-medium text-gray-900">Account Status</p>
              <p className="text-sm text-gray-600">Your account is active</p>
            </div>
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Active
            </span>
          </div>
          
          <div className="flex justify-between items-center py-3 border-b border-gray-200">
            <div>
              <p className="text-sm font-medium text-gray-900">Account Type</p>
              <p className="text-sm text-gray-600">Standard {profile?.role} account</p>
            </div>
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
              {profile?.role}
            </span>
          </div>

          <div className="py-3">
            <div>
              <p className="text-sm font-medium text-gray-900">Last Updated</p>
              <p className="text-sm text-gray-600">
                Profile was last updated on {profile?.updated_at ? new Date(profile.updated_at).toLocaleDateString() : 'Unknown'}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Profile;

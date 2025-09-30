import React, { useState, FormEvent } from 'react';
import { useAdminAuth } from '@/hooks/admin/useAdminAuth';
import { PixelButton } from '@/components/admin/ui/PixelButton';
import { PixelInput } from '@/components/admin/ui/PixelInput';
import { AdminError } from '@/types/admin';

interface AdminLoginProps {
  onLoginSuccess?: () => void;
}

export const AdminLogin: React.FC<AdminLoginProps> = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<AdminError | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ username?: string; password?: string }>({});

  const { login } = useAdminAuth();

  const validateForm = (): boolean => {
    const errors: { username?: string; password?: string } = {};
    
    if (!username.trim()) {
      errors.username = 'Username is required';
    }
    
    if (!password.trim()) {
      errors.password = 'Password is required';
    } else if (password.length < 6) {
      errors.password = 'Password must be at least 6 characters';
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setLoading(true);
    setError(null);
    setFieldErrors({});

    try {
      const success = await login(username.trim(), password);
      
      if (success) {
        onLoginSuccess?.();
      } else {
        setError({
          type: 'authentication',
          message: 'Invalid username or password. Please try again.',
          code: 'INVALID_CREDENTIALS'
        });
      }
    } catch (err) {
      setError({
        type: 'network',
        message: 'Login failed. Please check your connection and try again.',
        code: 'NETWORK_ERROR'
      });
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (field: 'username' | 'password', value: string) => {
    if (field === 'username') {
      setUsername(value);
      if (fieldErrors.username) {
        setFieldErrors(prev => ({ ...prev, username: undefined }));
      }
    } else {
      setPassword(value);
      if (fieldErrors.password) {
        setFieldErrors(prev => ({ ...prev, password: undefined }));
      }
    }
    
    if (error) {
      setError(null);
    }
  };

  return (
    <div className="min-h-screen bg-gray-900 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-block p-4 bg-gray-800 border-2 border-gray-600 mb-4">
            <div className="w-16 h-16 bg-blue-600 border-2 border-blue-800 flex items-center justify-center">
              <span className="text-2xl font-mono text-white">👤</span>
            </div>
          </div>
          <h1 className="text-3xl font-mono text-white mb-2">Admin Login</h1>
          <p className="text-gray-400 font-mono">Enter your credentials to access the dashboard</p>
        </div>

        {/* Login Form */}
        <div className="bg-gray-800 border-2 border-gray-600 p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Global Error */}
            {error && (
              <div className="bg-red-900 border-2 border-red-700 p-3">
                <p className="text-red-300 font-mono text-sm">{error.message}</p>
              </div>
            )}

            {/* Username Field */}
            <PixelInput
              label="Username"
              type="text"
              value={username}
              onChange={(e) => handleInputChange('username', e.target.value)}
              error={fieldErrors.username}
              placeholder="Enter your username"
              disabled={loading}
              autoComplete="username"
              autoFocus
            />

            {/* Password Field */}
            <PixelInput
              label="Password"
              type="password"
              value={password}
              onChange={(e) => handleInputChange('password', e.target.value)}
              error={fieldErrors.password}
              placeholder="Enter your password"
              disabled={loading}
              autoComplete="current-password"
            />

            {/* Submit Button */}
            <PixelButton
              type="submit"
              variant="primary"
              size="lg"
              loading={loading}
              className="w-full"
            >
              {loading ? 'Logging in...' : 'Login'}
            </PixelButton>
          </form>

          {/* Demo Credentials Info */}
          <div className="mt-6 pt-4 border-t-2 border-gray-600">
            <p className="text-gray-400 font-mono text-xs text-center mb-2">Demo Credentials:</p>
            <div className="bg-gray-700 border border-gray-600 p-2 rounded">
              <p className="text-gray-300 font-mono text-xs">Username: admin</p>
              <p className="text-gray-300 font-mono text-xs">Password: admin123</p>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="text-center mt-6">
          <p className="text-gray-500 font-mono text-xs">
            © 2024 Nhật Anh Dev - Admin Dashboard
          </p>
        </div>
      </div>
    </div>
  );
};
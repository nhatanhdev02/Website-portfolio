import React, { Suspense, lazy } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AdminProvider } from '@/contexts/AdminContext';
import { AdminErrorBoundary } from '@/components/admin/ErrorBoundary';
import { ProtectedRoute } from '@/components/admin/ProtectedRoute';
import { AdminLayout } from '@/components/admin/layout/AdminLayout';
import { AdminLoadingSpinner } from '@/components/admin/ui/AdminLoadingSpinner';

// Lazy load admin pages for code splitting
const AdminDashboard = lazy(() => import('@/components/admin/pages/AdminDashboard').then(module => ({ default: module.AdminDashboard })));
const SystemSettings = lazy(() => import('@/components/admin/pages/SystemSettings').then(module => ({ default: module.SystemSettings })));
const HeroManager = lazy(() => import('@/components/admin/pages/HeroManager').then(module => ({ default: module.HeroManager })));
const AboutManager = lazy(() => import('@/components/admin/pages/AboutManager').then(module => ({ default: module.AboutManager })));
const ServicesManager = lazy(() => import('@/components/admin/pages/ServicesManager').then(module => ({ default: module.ServicesManager })));
const PortfolioManager = lazy(() => import('@/components/admin/pages/PortfolioManager').then(module => ({ default: module.PortfolioManager })));
const BlogManager = lazy(() => import('@/components/admin/pages/BlogManager').then(module => ({ default: module.BlogManager })));
const ContactManager = lazy(() => import('@/components/admin/pages/ContactManager').then(module => ({ default: module.ContactManager })));

export const AdminApp: React.FC = () => {
  return (
    <AdminErrorBoundary>
      <AdminProvider>
        <Routes>
        {/* Admin Dashboard Routes */}
        <Route path="/dashboard" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <AdminDashboard />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />
        
        <Route path="/settings" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <SystemSettings />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />

        {/* Hero Management */}
        <Route path="/hero" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <HeroManager />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />

        {/* About Management */}
        <Route path="/about" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <AboutManager />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />

        {/* Services Management */}
        <Route path="/services" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <ServicesManager />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />

        {/* Portfolio Management */}
        <Route path="/portfolio" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <PortfolioManager />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />

        {/* Blog Management */}
        <Route path="/blog" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <BlogManager />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />

        {/* Contact Management */}
        <Route path="/contact" element={
          <ProtectedRoute>
            <AdminLayout>
              <Suspense fallback={<AdminLoadingSpinner />}>
                <ContactManager />
              </Suspense>
            </AdminLayout>
          </ProtectedRoute>
        } />

        {/* Default redirect to dashboard */}
        <Route path="/" element={<Navigate to="/admin/dashboard" replace />} />
        <Route path="*" element={<Navigate to="/admin/dashboard" replace />} />
      </Routes>
    </AdminProvider>
    </AdminErrorBoundary>
  );
};
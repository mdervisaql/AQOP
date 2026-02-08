import { StrictMode, lazy, Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './auth/AuthContext';
import { ProtectedRoute } from './auth/ProtectedRoute';
import { ROLES } from './utils/constants';
import LoadingSpinner from './components/LoadingSpinner';
import FeedbackWidget from './components/FeedbackWidget';
import { ActivityTrackerWrapper } from './components/ActivityTrackerWrapper';
import './index.css';

import { NotificationProvider } from './contexts/NotificationContext';
import { ToastProvider } from './components/Toast';
import BottomNav from './components/BottomNav';

// Lazy Load Pages
const LoginPage = lazy(() => import('./pages/LoginPage'));
const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const MyLeads = lazy(() => import('./pages/Agent/MyLeads'));
const LeadDetail = lazy(() => import('./pages/Agent/LeadDetail'));
const AllLeads = lazy(() => import('./pages/Manager/AllLeads'));
const Analytics = lazy(() => import('./pages/Manager/Analytics'));
const Automation = lazy(() => import('./pages/Manager/Automation'));
const BulkWhatsAppJobs = lazy(() => import('./pages/Manager/BulkWhatsAppJobs'));
const ReportsDashboard = lazy(() => import('./pages/Manager/Reports/ReportsDashboard'));
const TeamLeads = lazy(() => import('./pages/Supervisor/TeamLeads'));
const UserManagement = lazy(() => import('./pages/Admin/UserManagement'));
const SystemHealth = lazy(() => import('./pages/Admin/SystemHealth'));
const LeadForm = lazy(() => import('./pages/Public/LeadForm'));
const NotificationsPage = lazy(() => import('./pages/NotificationsPage'));
const NotificationSettingsPage = lazy(() => import('./pages/NotificationSettingsPage'));

const FollowUpsPage = lazy(() => import('./pages/FollowUpsPage'));
const ProfilePage = lazy(() => import('./pages/ProfilePage'));
const FacebookLeadsSettings = lazy(() => import('./pages/Settings/FacebookLeadsSettings'));

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // Data is fresh for 5 minutes
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <NotificationProvider>
          <BrowserRouter>
            <ActivityTrackerWrapper>
              <Suspense
                fallback={
                  <div className="flex items-center justify-center min-h-screen bg-gray-50">
                    <LoadingSpinner size="lg" text="Loading application..." />
                  </div>
                }
              >
                <Routes>
                  {/* Public Routes */}
                  <Route path="/login" element={<LoginPage />} />

                  {/* Protected Routes - Dashboard */}
                  <Route
                    path="/dashboard"
                    element={
                      <ProtectedRoute>
                        <DashboardPage />
                      </ProtectedRoute>
                    }
                  />

                  {/* Protected Routes - Notifications */}
                  <Route
                    path="/notifications"
                    element={
                      <ProtectedRoute>
                        <NotificationsPage />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/settings/notifications"
                    element={
                      <ProtectedRoute>
                        <NotificationSettingsPage />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/follow-ups"
                    element={
                      <ProtectedRoute>
                        <FollowUpsPage />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/settings/facebook-leads"
                    element={
                      <ProtectedRoute requiredRole={ROLES.DIGITAL_MARKETING}>
                        <FacebookLeadsSettings />
                      </ProtectedRoute>
                    }
                  />

                  {/* Protected Routes - Agent Pages */}
                  <Route
                    path="/leads"
                    element={
                      <ProtectedRoute requiredRole={ROLES.AGENT}>
                        <MyLeads />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/leads/:id"
                    element={
                      <ProtectedRoute requiredRole={ROLES.AGENT}>
                        <LeadDetail />
                      </ProtectedRoute>
                    }
                  />

                  {/* Protected Routes - Manager Pages */}
                  <Route
                    path="/manager/all-leads"
                    element={
                      <ProtectedRoute requiredRole={ROLES.OPERATION_MANAGER}>
                        <AllLeads />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/manager/analytics"
                    element={
                      <ProtectedRoute requiredRole={ROLES.OPERATION_MANAGER}>
                        <Analytics />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/manager/automation"
                    element={
                      <ProtectedRoute requiredRole={ROLES.OPERATION_MANAGER}>
                        <Automation />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/manager/bulk-whatsapp"
                    element={
                      <ProtectedRoute requiredRole={ROLES.OPERATION_MANAGER}>
                        <BulkWhatsAppJobs />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/manager/reports"
                    element={
                      <ProtectedRoute requiredRole={ROLES.OPERATION_MANAGER}>
                        <ReportsDashboard />
                      </ProtectedRoute>
                    }
                  />

                  {/* Protected Routes - Supervisor Pages */}
                  <Route
                    path="/supervisor/team-leads"
                    element={
                      <ProtectedRoute requiredRole={ROLES.SUPERVISOR}>
                        <TeamLeads />
                      </ProtectedRoute>
                    }
                  />

                  {/* Protected Routes - Profile */}
                  <Route
                    path="/profile"
                    element={
                      <ProtectedRoute>
                        <ProfilePage />
                      </ProtectedRoute>
                    }
                  />
                  <Route
                    path="/notification-settings"
                    element={
                      <ProtectedRoute>
                        <NotificationSettingsPage />
                      </ProtectedRoute>
                    }
                  />

                  {/* Protected Routes - Admin Pages */}
                  <Route
                    path="/admin/users"
                    element={
                      <ProtectedRoute requiredRole={ROLES.OPERATION_ADMIN}>
                        <UserManagement />
                      </ProtectedRoute>
                    }
                  />

                  {/* Public Routes - No Authentication Required */}
                  <Route path="/submit-lead" element={<LeadForm />} />

                  {/* Default Route */}
                  <Route path="/" element={<Navigate to="/dashboard" replace />} />

                  {/* 404 Route */}
                  <Route path="*" element={<Navigate to="/dashboard" replace />} />
                </Routes>
                <FeedbackWidget />
                <BottomNav />
              </Suspense>
            </ActivityTrackerWrapper>
          </BrowserRouter>
        </NotificationProvider>
      </AuthProvider>
    </QueryClientProvider>
  </StrictMode>
);

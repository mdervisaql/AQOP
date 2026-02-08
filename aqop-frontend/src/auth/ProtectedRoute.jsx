/**
 * Protected Route Component
 * 
 * Wraps routes that require authentication and optional role authorization.
 */

import { Navigate } from 'react-router-dom';
import { useAuth } from './AuthContext';
import { hasRole, getDefaultRoute, getRoleDisplayName } from '../utils/roleHelpers';

export const ProtectedRoute = ({ children, requiredRole = null, showAccessDenied = true }) => {
  const { isAuth, user, loading } = useAuth();

  // Show loading state
  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  // Not authenticated - redirect to login
  if (!isAuth) {
    return <Navigate to="/login" replace />;
  }

  // Authenticated but no role requirement - allow access
  if (!requiredRole) {
    return children;
  }

  // Check role authorization
  const hasAccess = hasRole(user, requiredRole);

  if (!hasAccess) {
    // Show access denied page or redirect to default route
    if (showAccessDenied) {
      return <AccessDeniedPage user={user} requiredRole={requiredRole} />;
    } else {
      // Redirect to user's default route based on their role
      const defaultRoute = getDefaultRoute(user);
      return <Navigate to={defaultRoute} replace />;
    }
  }

  return children;
};

/**
 * Access Denied Page Component
 */
const AccessDeniedPage = ({ user, requiredRole }) => {
  const userRoleDisplay = user?.role ? getRoleDisplayName(user.role) : 'Unknown';
  const requiredRoleDisplay = getRoleDisplayName(requiredRole);
  const defaultRoute = getDefaultRoute(user);

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full">
        <div className="bg-white rounded-lg shadow-xl p-8">
          {/* Icon */}
          <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg className="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>

          {/* Title */}
          <h2 className="text-2xl font-bold text-gray-900 text-center mb-2">
            Access Denied
          </h2>

          {/* Message */}
          <div className="text-center mb-6">
            <p className="text-gray-600 mb-2">
              You don't have permission to access this page.
            </p>
            <div className="text-sm text-gray-500 space-y-1">
              <p>
                <span className="font-medium">Your Role:</span> {userRoleDisplay}
              </p>
              <p>
                <span className="font-medium">Required:</span> {requiredRoleDisplay} or higher
              </p>
            </div>
          </div>

          {/* Action Button */}
          <a
            href={defaultRoute}
            className="block w-full text-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Go to My Dashboard
          </a>

          {/* Help Text */}
          <p className="mt-4 text-xs text-center text-gray-500">
            If you believe you should have access, please contact your administrator.
          </p>
        </div>
      </div>
    </div>
  );
};


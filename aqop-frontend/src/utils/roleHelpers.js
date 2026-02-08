/**
 * Role Helper Functions
 * 
 * Utilities for role-based access control with hierarchy support.
 */

import { ROLES } from './constants';

/**
 * Role hierarchy levels
 * Higher number = more permissions
 */
const ROLE_HIERARCHY = {
  [ROLES.ADMIN]: 100,                    // administrator - Full access
  [ROLES.OPERATION_ADMIN]: 90,           // operation_admin - Full access
  [ROLES.OPERATION_MANAGER]: 80,         // operation_manager - Manager + Supervisor + Agent
  [ROLES.SUPERVISOR]: 50,                 // aq_supervisor - Supervisor + Agent
  [ROLES.AGENT]: 10,                      // aq_agent - Agent only
};

/**
 * Check if user has specific role
 * 
 * @param {Object} user - User object
 * @param {string} role - Role to check
 * @returns {boolean}
 */
export const hasExactRole = (user, role) => {
  if (!user || !user.role) return false;
  return user.role === role;
};

/**
 * Check if user has required role or higher in hierarchy
 * 
 * @param {Object} user - User object
 * @param {string} requiredRole - Minimum required role
 * @returns {boolean}
 */
export const hasRole = (user, requiredRole) => {
  if (!user || !user.role) return false;
  
  const userLevel = ROLE_HIERARCHY[user.role] || 0;
  const requiredLevel = ROLE_HIERARCHY[requiredRole] || 0;
  
  return userLevel >= requiredLevel;
};

/**
 * Check if user has any of the specified roles (with hierarchy)
 * 
 * @param {Object} user - User object
 * @param {string[]} roles - Array of acceptable roles
 * @returns {boolean}
 */
export const hasAnyRole = (user, roles) => {
  if (!user || !user.role || !Array.isArray(roles)) return false;
  
  // Check if user has any of the specified roles or higher
  return roles.some(role => hasRole(user, role));
};

/**
 * Get user's role level
 * 
 * @param {Object} user - User object
 * @returns {number} Role level (0 if no role)
 */
export const getRoleLevel = (user) => {
  if (!user || !user.role) return 0;
  return ROLE_HIERARCHY[user.role] || 0;
};

/**
 * Get default route for user's role
 * 
 * @param {Object} user - User object
 * @returns {string} Default route path
 */
export const getDefaultRoute = (user) => {
  if (!user || !user.role) return '/login';
  
  const roleLevel = getRoleLevel(user);
  
  if (roleLevel >= ROLE_HIERARCHY[ROLES.OPERATION_MANAGER]) {
    return '/manager/all-leads';
  } else if (roleLevel >= ROLE_HIERARCHY[ROLES.SUPERVISOR]) {
    return '/supervisor/team-leads';
  } else if (roleLevel >= ROLE_HIERARCHY[ROLES.AGENT]) {
    return '/leads';
  }
  
  return '/dashboard';
};

/**
 * Check if user can access a specific route
 * 
 * @param {Object} user - User object
 * @param {string} requiredRole - Minimum role required
 * @returns {boolean}
 */
export const canAccessRoute = (user, requiredRole) => {
  if (!requiredRole) return true; // No role requirement
  return hasRole(user, requiredRole);
};

/**
 * Get role display name
 * 
 * @param {string} role - Role key
 * @returns {string} Human-readable role name
 */
export const getRoleDisplayName = (role) => {
  const roleNames = {
    [ROLES.ADMIN]: 'Administrator',
    [ROLES.OPERATION_ADMIN]: 'Operation Admin',
    [ROLES.OPERATION_MANAGER]: 'Operation Manager',
    [ROLES.SUPERVISOR]: 'Supervisor',
    [ROLES.AGENT]: 'Agent',
  };
  
  return roleNames[role] || role;
};


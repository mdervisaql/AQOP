/**
 * Application Constants
 */

// Single source of truth for the API base URL (defined in api/index.js).
export { API_URL } from '../api/index';

export const TOKEN_KEYS = {
  ACCESS: 'access_token',
  REFRESH: 'refresh_token',
  USER: 'user',
};

export const ROLES = {
  ADMIN: 'administrator',
  OPERATION_ADMIN: 'operation_admin',
  OPERATION_MANAGER: 'operation_manager',
  COUNTRY_MANAGER: 'aq_country_manager',
  SUPERVISOR: 'aq_supervisor',
  AGENT: 'aq_agent',
  DIGITAL_MARKETING: 'digital_marketing',
};

export const ROUTES = {
  LOGIN: '/login',
  DASHBOARD: '/dashboard',
  LEADS: '/leads',
  SETTINGS: '/settings',
};


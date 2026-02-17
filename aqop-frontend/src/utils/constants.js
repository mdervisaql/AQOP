/**
 * Application Constants
 */

export const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8888/aqleeat-operation/wp-json';

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


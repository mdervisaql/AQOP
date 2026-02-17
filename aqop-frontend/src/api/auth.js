/**
 * Authentication API
 * 
 * Handles all authentication-related API calls.
 */

import apiClient from './index';

const AUTH_ENDPOINTS = {
  LOGIN: '/aqop-jwt/v1/login',
  REFRESH: '/aqop-jwt/v1/refresh',
  LOGOUT: '/aqop-jwt/v1/logout',
  VALIDATE: '/aqop-jwt/v1/validate',
};

/**
 * Login user
 */
export const login = async (username, password) => {
  const response = await apiClient.post(AUTH_ENDPOINTS.LOGIN, {
    username,
    password,
  });

  if (response.success && response.data) {
    // Store tokens
    localStorage.setItem('access_token', response.data.access_token);
    localStorage.setItem('refresh_token', response.data.refresh_token);
    localStorage.setItem('user', JSON.stringify(response.data.user));

    // Store session token for activity tracking
    if (response.data.session_token) {
      localStorage.setItem('session_token', response.data.session_token);
    }
  }

  return response;
};

/**
 * Logout user
 */
export const logout = async () => {
  try {
    await apiClient.post(AUTH_ENDPOINTS.LOGOUT);
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    // Clear local storage
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');
    localStorage.removeItem('session_token');
  }
};

/**
 * Refresh access token
 */
export const refreshToken = async () => {
  const refreshToken = localStorage.getItem('refresh_token');

  if (!refreshToken) {
    throw new Error('No refresh token available');
  }

  const response = await apiClient.post(AUTH_ENDPOINTS.REFRESH, {
    refresh_token: refreshToken,
  });

  if (response.success && response.data) {
    localStorage.setItem('access_token', response.data.access_token);
  }

  return response;
};

/**
 * Validate token
 */
export const validateToken = async (token) => {
  const response = await apiClient.post(AUTH_ENDPOINTS.VALIDATE, {
    token: token || localStorage.getItem('access_token'),
  });

  return response;
};

/**
 * Get current user from storage
 */
export const getCurrentUser = () => {
  const userStr = localStorage.getItem('user');
  return userStr ? JSON.parse(userStr) : null;
};

/**
 * Check if user is authenticated
 */
export const isAuthenticated = () => {
  return !!localStorage.getItem('access_token');
};


/**
 * Users API
 * 
 * Handles user-related API calls (for managers and admins).
 */

import apiClient from './index';

/**
 * Get all users with AQOP roles
 * 
 * @param {Object} params - Query parameters
 * @returns {Promise} API response
 */
export const getAqopUsers = async (params = {}) => {
  try {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString ? `/aqop/v1/users?${queryString}` : '/aqop/v1/users';
    return await apiClient.get(endpoint);
  } catch (error) {
    console.error('Error fetching AQOP users:', error);
    throw error;
  }
};

/**
 * Get all agents (for assignment dropdowns)
 * 
 * @returns {Promise} API response
 */
export const getAgents = async () => {
  try {
    const response = await getAqopUsers({ role: 'aq_agent,aq_supervisor' });

    if (response.success && response.data) {
      // Format for dropdown
      return response.data.map(user => ({
        id: user.id,
        name: user.display_name || user.username,
        role: user.role,
      }));
    }

    return [];
  } catch (error) {
    console.error('Error fetching agents:', error);
    return [];
  }
};

/**
 * Get single user by ID
 * 
 * @param {number} userId - User ID
 * @returns {Promise} API response
 */
export const getUser = async (userId) => {
  return await apiClient.get(`/aqop/v1/users/${userId}`);
};

/**
 * Create new user
 * 
 * @param {Object} userData - User data
 * @returns {Promise} API response
 */
export const createUser = async (userData) => {
  return await apiClient.post('/aqop/v1/users', userData);
};

/**
 * Update user
 * 
 * @param {number} userId - User ID
 * @param {Object} userData - Updated user data
 * @returns {Promise} API response
 */
export const updateUser = async (userId, userData) => {
  return await apiClient.post(`/aqop/v1/users/${userId}`, userData);
};

/**
 * Delete user
 * 
 * @param {number} userId - User ID
 * @returns {Promise} API response
 */
export const deleteUser = async (userId) => {
  return await apiClient.delete(`/aqop/v1/users/${userId}`);
};

/**
 * Get team statistics (placeholder for future)
 * 
 * @returns {Promise} API response
 */
export const getTeamStats = async () => {
  try {
    const response = await apiClient.get('/aqop/v1/users/stats');
    return response;
  } catch (error) {
    console.error('Error fetching team stats:', error);
    return null;
  }
};

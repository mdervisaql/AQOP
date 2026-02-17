/**
 * Leads API
 * 
 * Handles all leads-related API calls.
 */

import apiClient from './index';

const LEADS_ENDPOINTS = {
  BASE: '/aqop/v1/leads',
  LIST: '/aqop/v1/leads',
  DETAIL: '/aqop/v1/leads',
  CREATE: '/aqop/v1/leads',
  UPDATE: '/aqop/v1/leads',
  DELETE: '/aqop/v1/leads',
  NOTES: '/aqop/v1/leads',
  UPLOAD: '/aqop/v1/leads',
  COUNTRIES: '/aqop/v1/leads/countries',
  STATS: '/aqop/v1/leads/stats',
};

/**
 * Get leads list
 * 
 * @param {Object} params - Query parameters
 * @returns {Promise} API response
 */
export const getLeads = async (params = {}) => {
  const queryString = new URLSearchParams(params).toString();
  const endpoint = queryString ? `${LEADS_ENDPOINTS.LIST}?${queryString}` : LEADS_ENDPOINTS.LIST;

  return await apiClient.get(endpoint);
};

/**
 * Get my assigned leads (for agents)
 * 
 * @param {Object} params - Query parameters
 * @returns {Promise} API response
 */
export const getMyLeads = async (params = {}) => {
  return await getLeads({ ...params, assigned_to_me: true });
};

/**
 * Get single lead by ID
 * 
 * @param {number} leadId - Lead ID
 * @returns {Promise} API response
 */
export const getLead = async (leadId) => {
  return await apiClient.get(`${LEADS_ENDPOINTS.DETAIL}/${leadId}`);
};

/**
 * Create new lead
 * 
 * @param {Object} leadData - Lead data
 * @returns {Promise} API response
 */
export const createLead = async (leadData) => {
  return await apiClient.post(LEADS_ENDPOINTS.CREATE, leadData);
};

/**
 * Update lead
 * 
 * @param {number} leadId - Lead ID
 * @param {Object} leadData - Updated lead data
 * @returns {Promise} API response
 */
export const updateLead = async (leadId, leadData) => {
  return await apiClient.put(`${LEADS_ENDPOINTS.UPDATE}/${leadId}`, leadData);
};

/**
 * Update lead status
 * 
 * @param {number} leadId - Lead ID
 * @param {string} statusCode - Status code (pending, contacted, qualified, converted, lost)
 * @param {Object} extras - Optional extra fields: { lostReason, dealStage }
 * @returns {Promise} API response
 */
export const updateLeadStatus = async (leadId, statusCode, extras = {}) => {
  const data = { status_code: statusCode };
  if (statusCode === 'lost' && extras.lostReason) {
    data.lost_reason = extras.lostReason;
  }
  if (statusCode === 'qualified' && extras.dealStage) {
    data.deal_stage = extras.dealStage;
  }
  return await updateLead(leadId, data);
};

/**
 * Delete lead
 * 
 * @param {number} leadId - Lead ID
 * @returns {Promise} API response
 */
export const deleteLead = async (leadId) => {
  return await apiClient.delete(`${LEADS_ENDPOINTS.DELETE}/${leadId}`);
};

/**
 * Add note to lead
 * 
 * @param {number} leadId - Lead ID
 * @param {string} noteText - Note content
 * @returns {Promise} API response
 */
export const addLeadNote = async (leadId, noteText) => {
  return await apiClient.post(`${LEADS_ENDPOINTS.NOTES}/${leadId}/notes`, {
    note_text: noteText,
  });
};

/**
 * Get lead notes
 * 
 * @param {number} leadId - Lead ID
 * @returns {Promise} API response
 */
export const getLeadNotes = async (leadId) => {
  return await apiClient.get(`${LEADS_ENDPOINTS.NOTES}/${leadId}/notes`);
};

/**
 * Upload file to lead
 * 
 * @param {number} leadId - Lead ID
 * @param {File} file - File object
 * @returns {Promise} API response
 */
export const uploadLeadFile = async (leadId, file) => {
  const formData = new FormData();
  formData.append('file', file);

  return await apiClient.post(`${LEADS_ENDPOINTS.BASE}/${leadId}/upload`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
};

/**
 * Get lead events/activity log
 * 
 * @param {number} leadId - Lead ID
 * @returns {Promise} API response
 */
export const getLeadEvents = async (leadId) => {
  return await apiClient.get(`${LEADS_ENDPOINTS.BASE}/${leadId}/events`);
};

/**
 * Get leads statistics
 * 
 * @returns {Promise} API response
 */
export const getLeadsStats = async () => {
  return await apiClient.get(LEADS_ENDPOINTS.STATS);
};

/**
 * Get funnel statistics with conversion rates
 * 
 * @returns {Promise} API response
 */
export const getFunnelStats = async () => {
  return await apiClient.get('/aqop/v1/leads/funnel-stats');
};

/**
 * Recalculate lead score
 * 
 * @param {number} leadId - Lead ID
 * @returns {Promise} API response
 */
export const recalculateLeadScore = async (leadId) => {
  return await apiClient.post(`${LEADS_ENDPOINTS.DETAIL}/${leadId}/recalculate-score`);
};

/**
 * Get lead score history
 * 
 * @param {number} leadId - Lead ID
 * @returns {Promise} API response
 */
export const getLeadScoreHistory = async (leadId) => {
  return await apiClient.get(`${LEADS_ENDPOINTS.DETAIL}/${leadId}/score-history`);
};

/**
 * Get available countries
 * 
 * @returns {Promise} API response
 */
export const getCountries = async () => {
  return await apiClient.get(LEADS_ENDPOINTS.COUNTRIES);
};

/**
 * Get learning paths
 * 
 * @returns {Promise} API response
 */
export const getLearningPaths = async () => {
  return await apiClient.get('/aqop/v1/leads/learning-paths');
};

/**
 * Create learning path (admin only)
 */
export const createLearningPath = async (data) => {
  return await apiClient.post('/aqop/v1/leads/learning-paths', data);
};

/**
 * Update learning path (admin only)
 */
export const updateLearningPath = async (id, data) => {
  return await apiClient.put(`/aqop/v1/leads/learning-paths/${id}`, data);
};

/**
 * Delete learning path (admin only)
 */
export const deleteLearningPath = async (id) => {
  return await apiClient.delete(`/aqop/v1/leads/learning-paths/${id}`);
};

/**
 * Get status badge color
 * 
 * @param {string} statusCode - Status code
 * @returns {string} Tailwind color class
 */
export const getStatusColor = (statusCode) => {
  const colors = {
    pending: 'bg-gray-100 text-gray-800',
    contacted: 'bg-blue-100 text-blue-800',
    qualified: 'bg-orange-100 text-orange-800',
    converted: 'bg-green-100 text-green-800',
    lost: 'bg-red-100 text-red-800',
  };
  return colors[statusCode] || colors.pending;
};

/**
 * Get priority badge color
 * 
 * @param {string} priority - Priority level
 * @returns {string} Tailwind color class
 */
export const getPriorityColor = (priority) => {
  const colors = {
    low: 'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-600',
    high: 'bg-orange-100 text-orange-600',
    urgent: 'bg-red-100 text-red-600',
  };
  return colors[priority] || colors.medium;
};


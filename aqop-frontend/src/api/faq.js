/**
 * FAQ API
 * 
 * Handles FAQ (questions & answers) for lead communication.
 */

import apiClient from './index';

const FAQ_BASE = '/aqop/v1/faqs';

/**
 * Get FAQs with optional filters
 */
export const getFaqs = async (params = {}) => {
  const queryParams = new URLSearchParams();
  if (params.country_id) queryParams.append('country_id', params.country_id);
  if (params.category) queryParams.append('category', params.category);
  if (params.search) queryParams.append('search', params.search);
  
  const query = queryParams.toString();
  return await apiClient.get(`${FAQ_BASE}${query ? `?${query}` : ''}`);
};

/**
 * Get FAQ categories
 */
export const getFaqCategories = async () => {
  return await apiClient.get(`${FAQ_BASE}/categories`);
};

/**
 * Create FAQ (admin/country manager)
 */
export const createFaq = async (data) => {
  return await apiClient.post(FAQ_BASE, data);
};

/**
 * Update FAQ
 */
export const updateFaq = async (id, data) => {
  return await apiClient.put(`${FAQ_BASE}/${id}`, data);
};

/**
 * Delete FAQ
 */
export const deleteFaq = async (id) => {
  return await apiClient.delete(`${FAQ_BASE}/${id}`);
};

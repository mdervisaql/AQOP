/**
 * Conversion Targets API Functions
 * 
 * Handles global and country-specific conversion targets.
 */

import apiClient from './index';

/**
 * Get conversion targets (global or by country)
 * @param {number|null} countryId - Country ID (optional, null = global)
 * @returns {Promise}
 */
export const getTargets = async (countryId = null) => {
  const params = countryId ? { country_id: countryId } : {};
  return await apiClient.get('/aqop/v1/targets', { params });
};

/**
 * Save conversion targets (global or country-specific)
 * @param {object} data - Targets data including country_id and target values
 * @returns {Promise}
 */
export const saveTargets = async (data) => {
  return await apiClient.post('/aqop/v1/targets', data);
};

/**
 * Delete country-specific targets (revert to global)
 * @param {number} countryId - Country ID
 * @returns {Promise}
 */
export const deleteCountryTargets = async (countryId) => {
  return await apiClient.delete(`/aqop/v1/targets/${countryId}`);
};

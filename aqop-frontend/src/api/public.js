/**
 * Public API
 * 
 * Handles public API calls (no authentication required).
 */

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8888/aqleeat-operation/wp-json';

/**
 * Submit public lead form
 * 
 * @param {Object} leadData - Lead data from form
 * @returns {Promise} API response
 */
export const submitPublicLead = async (leadData) => {
  const url = `${API_URL}/aqop/v1/leads/public`;
  
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(leadData),
    });

    const data = await response.json();

    if (!response.ok) {
      // Extract error message from WordPress error format
      const errorMessage = data.message || (data.code === 'rate_limit_exceeded' 
        ? 'Too many submissions. Please try again in 10 minutes.'
        : 'Failed to submit lead');
      throw new Error(errorMessage);
    }

    return data;
  } catch (error) {
    console.error('Public lead submission error:', error);
    throw error;
  }
};


import api from './index';

const BASE_URL = '/aqop/v1';

/**
 * Get communications for a lead
 */
export const getCommunications = async (leadId) => {
    try {
        const response = await api.get(`${BASE_URL}/leads/${leadId}/communications`);
        return response.data;
    } catch (error) {
        console.error('Error fetching communications:', error);
        throw error;
    }
};

/**
 * Add new communication
 */
export const addCommunication = async (leadId, data) => {
    try {
        const response = await api.post(`${BASE_URL}/leads/${leadId}/communications`, data);
        return response.data;
    } catch (error) {
        console.error('Error adding communication:', error);
        throw error;
    }
};

/**
 * Update communication
 */
export const updateCommunication = async (id, data) => {
    try {
        const response = await api.post(`${BASE_URL}/communications/${id}`, data); // Using POST for update as per WP REST API patterns often used here, or PUT if supported
        return response.data;
    } catch (error) {
        console.error('Error updating communication:', error);
        throw error;
    }
};

/**
 * Delete communication
 */
export const deleteCommunication = async (id) => {
    try {
        const response = await api.delete(`${BASE_URL}/communications/${id}`);
        return response.data;
    } catch (error) {
        console.error('Error deleting communication:', error);
        throw error;
    }
};

/**
 * Get follow-ups
 */
export const getFollowUps = async (params = {}) => {
    try {
        const response = await api.get(`${BASE_URL}/follow-ups`, { params });
        return response.data;
    } catch (error) {
        console.error('Error fetching follow-ups:', error);
        throw error;
    }
};

/**
 * Get today's follow-ups
 */
export const getTodayFollowUps = async () => {
    try {
        const response = await api.get(`${BASE_URL}/follow-ups/today`);
        return response.data;
    } catch (error) {
        console.error('Error fetching today follow-ups:', error);
        throw error;
    }
};

/**
 * Complete follow-up
 */
export const completeFollowUp = async (id) => {
    try {
        const response = await api.post(`${BASE_URL}/follow-ups/${id}/complete`);
        return response.data;
    } catch (error) {
        console.error('Error completing follow-up:', error);
        throw error;
    }
};

/**
 * Create a standalone follow-up (next step)
 * 
 * @param {Object} data - Follow-up data
 * @param {number} data.lead_id - Lead ID
 * @param {string} data.title - Next step title/description
 * @param {string} data.description - Detailed description
 * @param {string} data.due_date - Date and time (YYYY-MM-DD HH:mm:ss)
 * @param {string} data.contact_method - Communication method (call, whatsapp, email, meeting, sms)
 * @param {string} data.priority - Priority (low, medium, high)
 * @returns {Promise} API response
 */
export const createFollowUp = async (data) => {
    try {
        const response = await api.post(`${BASE_URL}/follow-ups`, data);
        return response.data;
    } catch (error) {
        console.error('Error creating follow-up:', error);
        throw error;
    }
};

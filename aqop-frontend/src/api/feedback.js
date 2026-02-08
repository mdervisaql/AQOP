import apiClient from './index';

/**
 * Create new feedback.
 * @param {Object} data Feedback data.
 * @returns {Promise<Object>} Created feedback.
 */
export const createFeedback = async (data) => {
    const response = await apiClient.post('/aqop/v1/feedback', data);
    return response.data;
};

/**
 * Get feedback list.
 * @param {Object} params Query parameters.
 * @returns {Promise<Object>} Feedback list.
 */
export const getFeedback = async (params = {}) => {
    const response = await apiClient.get('/aqop/v1/feedback', { params });
    return response.data;
};

/**
 * Get single feedback.
 * @param {number} id Feedback ID.
 * @returns {Promise<Object>} Feedback data.
 */
export const getFeedbackItem = async (id) => {
    const response = await apiClient.get(`/aqop/v1/feedback/${id}`);
    return response.data;
};

/**
 * Update feedback.
 * @param {number} id Feedback ID.
 * @param {Object} data Update data.
 * @returns {Promise<Object>} Updated feedback.
 */
export const updateFeedback = async (id, data) => {
    const response = await apiClient.put(`/aqop/v1/feedback/${id}`, data);
    return response.data;
};

/**
 * Add comment to feedback.
 * @param {number} id Feedback ID.
 * @param {string} comment_text Comment text.
 * @param {boolean} is_internal Is internal comment.
 * @returns {Promise<Object>} Comments list.
 */
export const addFeedbackComment = async (id, comment_text, is_internal = false) => {
    const response = await apiClient.post(`/aqop/v1/feedback/${id}/comments`, {
        comment_text,
        is_internal,
    });
    return response.data;
};

/**
 * Get feedback comments.
 * @param {number} id Feedback ID.
 * @returns {Promise<Object>} Comments list.
 */
export const getFeedbackComments = async (id) => {
    const response = await apiClient.get(`/aqop/v1/feedback/${id}/comments`);
    return response.data;
};

/**
 * Get feedback statistics.
 * @returns {Promise<Object>} Statistics data.
 */
export const getFeedbackStats = async () => {
    const response = await apiClient.get('/aqop/v1/feedback/stats');
    return response.data;
};

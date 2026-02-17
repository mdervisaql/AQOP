import apiClient from './index';

/**
 * Get system health status.
 * @returns {Promise<Object>} Health data.
 */
export const getSystemHealth = async () => {
    const response = await apiClient.get('/aqop/v1/system/health');
    return response.data;
};

/**
 * Get system statistics.
 * @param {number} days Number of days to look back.
 * @returns {Promise<Object>} Statistics data.
 */
export const getSystemStats = async (days = 7) => {
    const response = await apiClient.get('/aqop/v1/system/stats', {
        params: { days },
    });
    return response.data;
};

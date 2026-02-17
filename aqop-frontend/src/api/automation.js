import api from './index';

export const automationApi = {
    // Rules
    getRules: () => api.get('/automation/rules'),
    createRule: (data) => api.post('/automation/rules', data),
    getRule: (id) => api.get(`/automation/rules/${id}`),
    updateRule: (id, data) => api.put(`/automation/rules/${id}`, data),
    deleteRule: (id) => api.delete(`/automation/rules/${id}`),
    toggleRule: (id, active) => api.post(`/automation/rules/${id}/toggle`, { active }),
    testRule: (id, leadId) => api.post(`/automation/rules/${id}/test`, { lead_id: leadId }),

    // Logs
    getLogs: (params) => api.get('/automation/logs', { params }),
};

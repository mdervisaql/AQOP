import api from './index';

export const getMessages = (leadId) => api.get(`/aqop/v1/leads/${leadId}/whatsapp/messages`);
export const sendMessage = (data) => api.post('/aqop/v1/whatsapp/send', data);

export const createBulkJob = (data) => api.post('/aqop/v1/whatsapp/bulk/create', data);
export const getBulkJobs = (params) => api.get('/aqop/v1/whatsapp/bulk/jobs', { params });
export const getBulkJob = (id) => api.get(`/aqop/v1/whatsapp/bulk/jobs/${id}`);
export const cancelBulkJob = (id) => api.post(`/aqop/v1/whatsapp/bulk/jobs/${id}/cancel`);
export const getBulkJobResults = (id) => api.get(`/aqop/v1/whatsapp/bulk/jobs/${id}/results`);
export const exportBulkJobResults = (id) => api.get(`/aqop/v1/whatsapp/bulk/jobs/${id}/export`, { responseType: 'blob' });

export const whatsappApi = {
    getMessages,
    sendMessage,
    createBulkJob,
    getBulkJobs,
    getBulkJob,
    cancelBulkJob,
    getBulkJobResults,
    exportBulkJobResults
};

import api from './index';

export const getAgentPerformanceReport = (params) => {
    return api.get('/reports/agent-performance', { params });
};

export const getSourceAnalysisReport = (params) => {
    return api.get('/reports/sources', { params });
};

export const getCampaignPerformanceReport = (params) => {
    return api.get('/reports/campaigns', { params });
};

export const getTimeAnalysisReport = (params) => {
    return api.get('/reports/time-analysis', { params });
};

export const getStatusDistributionReport = (params) => {
    return api.get('/reports/status-distribution', { params });
};

export const getCountryAnalysisReport = (params) => {
    return api.get('/reports/countries', { params });
};

export const getSummaryReport = (params) => {
    return api.get('/reports/summary', { params });
};

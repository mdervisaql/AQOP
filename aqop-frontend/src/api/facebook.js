import api from './index';

const facebookApi = {
    // OAuth
    getOAuthUrl: (redirectUri) => api.get('/aqop/v1/facebook/oauth-url', { params: { redirect_uri: redirectUri } }),
    handleOAuthCallback: (code, redirectUri) => api.post('/aqop/v1/facebook/oauth-callback', { code, redirect_uri: redirectUri }),

    // Connection
    getConnection: () => api.get('/aqop/v1/facebook/connection'),
    disconnect: () => api.post('/aqop/v1/facebook/disconnect'),

    // Resources
    getAdAccounts: () => api.get('/aqop/v1/facebook/ad-accounts'),
    getPages: () => api.get('/aqop/v1/facebook/pages'),
    getForms: (pageId, pageAccessToken) => api.get('/aqop/v1/facebook/forms', { params: { page_id: pageId, page_access_token: pageAccessToken } }),
    getFormFields: (formId, accessToken) => api.get(`/aqop/v1/facebook/forms/${formId}/fields`, { params: { access_token: accessToken } }),

    // Mapping
    saveMapping: (formId, data) => api.post(`/aqop/v1/facebook/forms/${formId}/map`, data),
};

export default facebookApi;

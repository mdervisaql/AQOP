/**
 * API Client Configuration
 * 
 * Centralized API client for all backend requests with automatic token refresh.
 */

const API_URL = import.meta.env.VITE_API_URL || 'https://operation.aqleeat.co/wp-json';

/**
 * Base API client with authentication support and token refresh interceptor
 */
class ApiClient {
  constructor() {
    this.baseURL = API_URL;
    this.isRefreshing = false;
    this.refreshQueue = [];
  }

  /**
   * Get authentication headers
   */
  getHeaders() {
    const token = localStorage.getItem('access_token');
    const headers = {
      'Content-Type': 'application/json',
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
  }

  /**
   * Refresh access token
   * 
   * @private
   * @returns {Promise<string>} New access token
   */
  async refreshAccessToken() {
    const refreshToken = localStorage.getItem('refresh_token');

    if (!refreshToken) {
      throw new Error('No refresh token available');
    }

    const url = `${this.baseURL}/aqop-jwt/v1/refresh`;
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ refresh_token: refreshToken }),
    });

    if (!response.ok) {
      throw new Error('Token refresh failed');
    }

    const data = await response.json();

    if (data.success && data.data && data.data.access_token) {
      const newToken = data.data.access_token;
      localStorage.setItem('access_token', newToken);
      return newToken;
    }

    throw new Error('Invalid refresh response');
  }

  /**
   * Logout user and redirect
   * 
   * @private
   */
  handleLogout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');

    // Redirect to login
    if (window.location.pathname !== '/login') {
      window.location.href = '/login';
    }
  }

  /**
   * Make API request with automatic token refresh on 401
   * 
   * @param {string} endpoint - API endpoint
   * @param {Object} options - Fetch options
   * @param {boolean} isRetry - Internal flag to prevent infinite loops
   * @returns {Promise} Response data
   */
  async request(endpoint, options = {}, isRetry = false) {
    const url = `${this.baseURL}${endpoint}`;
    const config = {
      ...options,
      headers: {
        ...this.getHeaders(),
        ...options.headers,
      },
    };

    try {
      const response = await fetch(url, config);

      // Handle 401 Unauthorized - Token expired
      if (response.status === 401 && !isRetry) {
        // If already refreshing, queue this request
        if (this.isRefreshing) {
          return new Promise((resolve, reject) => {
            this.refreshQueue.push({ resolve, reject, endpoint, options });
          });
        }

        // Start refresh process
        this.isRefreshing = true;

        try {
          // Refresh the token
          const newToken = await this.refreshAccessToken();

          // Retry original request with new token
          const retryResponse = await this.request(endpoint, options, true);

          // Process queued requests
          this.refreshQueue.forEach(({ resolve, endpoint, options }) => {
            this.request(endpoint, options, true).then(resolve).catch(reject => reject);
          });
          this.refreshQueue = [];

          return retryResponse;
        } catch (refreshError) {
          // Refresh failed - logout user
          console.error('Token refresh failed:', refreshError);
          this.handleLogout();

          // Reject queued requests
          this.refreshQueue.forEach(({ reject }) => {
            reject(new Error('Session expired. Please login again.'));
          });
          this.refreshQueue = [];

          throw new Error('Session expired. Please login again.');
        } finally {
          this.isRefreshing = false;
        }
      }

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'API request failed');
      }

      return data;
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }

  /**
   * GET request
   */
  async get(endpoint) {
    return this.request(endpoint, { method: 'GET' });
  }

  /**
   * POST request
   */
  async post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  /**
   * PUT request
   */
  async put(endpoint, data) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  /**
   * DELETE request
   */
  async delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

export const apiClient = new ApiClient();
export default apiClient;


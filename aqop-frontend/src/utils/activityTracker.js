/**
 * Activity Tracker Utility
 * 
 * Handles logging and batching of user activities.
 */

import apiClient from '../api';

const BATCH_INTERVAL = 30000; // 30 seconds
const MAX_BATCH_SIZE = 20;

let activityQueue = [];
let flushInterval = null;

/**
 * Log an activity
 * 
 * @param {string} type - Activity type (e.g., 'page_view', 'lead_view')
 * @param {Object} details - Additional details
 */
export const logActivity = (type, details = {}) => {
    // Add to queue
    activityQueue.push({
        type,
        details,
        timestamp: new Date().toISOString(),
        session_id: getSessionId()
    });

    // If queue is full, flush immediately
    if (activityQueue.length >= MAX_BATCH_SIZE) {
        flushActivities();
    }
};

/**
 * Flush queued activities to server
 */
export const flushActivities = async () => {
    if (activityQueue.length === 0) return;

    const batch = [...activityQueue];
    activityQueue = []; // Clear queue

    try {
        await apiClient.post('/aqop/v1/activity/log', { batch });
    } catch (error) {
        console.error('Failed to log activities:', error);
        // Optional: Retry logic or re-queue failed items (careful of infinite loops)
    }
};

/**
 * Initialize tracker
 */
export const initTracker = () => {
    // Set up periodic flush
    if (!flushInterval) {
        flushInterval = setInterval(flushActivities, BATCH_INTERVAL);
    }

    // Flush on page unload
    window.addEventListener('beforeunload', () => {
        flushActivities();
    });
};

/**
 * Get or create session ID
 */
const getSessionId = () => {
    let sessionId = sessionStorage.getItem('aqop_session_id');
    if (!sessionId) {
        sessionId = Math.random().toString(36).substring(2) + Date.now().toString(36);
        sessionStorage.setItem('aqop_session_id', sessionId);
    }
    return sessionId;
};

// Auto-init
initTracker();

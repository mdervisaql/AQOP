/**
 * Activity Tracker Hook
 * 
 * Automatically tracks user activity and sends heartbeat to server.
 */

import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { logActivity } from '../utils/activityTracker';

/**
 * Activity Tracker Hook
 * 
 * Usage: useActivityTracker()
 */
export const useActivityTracker = () => {
    const location = useLocation();
    const sessionToken = localStorage.getItem('session_token');

    useEffect(() => {
        if (!sessionToken) return;

        // Log page view
        logActivity('page_view', {
            path: location.pathname,
            search: location.search,
            title: document.title
        });

    }, [location.pathname, location.search, sessionToken]);
};

/**
 * Get session token
 */
export const getSessionToken = () => {
    return localStorage.getItem('session_token');
};

/**
 * Check if tracking is active
 */
export const isTrackingActive = () => {
    return !!localStorage.getItem('session_token');
};

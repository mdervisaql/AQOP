/**
 * Activity Tracker Wrapper
 * 
 * Wraps the app to enable automatic activity tracking.
 */

import { useActivityTracker } from '../hooks/useActivityTracker';

export const ActivityTrackerWrapper = ({ children }) => {
    // Enable activity tracking
    useActivityTracker();

    return children;
};

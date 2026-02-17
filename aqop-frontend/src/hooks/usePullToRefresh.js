import { useState, useCallback, useRef } from 'react';

/**
 * Custom hook for pull-to-refresh functionality
 * 
 * @param {Function} onRefresh - Async function to call when refresh is triggered
 * @param {Object} options - Configuration options
 * @param {number} options.threshold - Distance in px to trigger refresh (default: 80)
 * @param {number} options.resistance - Pull resistance factor (default: 2.5)
 * @returns {Object} Hook state and handlers
 */
export function usePullToRefresh(onRefresh, options = {}) {
    const { threshold = 80, resistance = 2.5 } = options;

    const [isPulling, setIsPulling] = useState(false);
    const [pullDistance, setPullDistance] = useState(0);
    const [isRefreshing, setIsRefreshing] = useState(false);

    const startY = useRef(0);
    const currentY = useRef(0);

    const handleTouchStart = useCallback((e) => {
        // Only enable pull-to-refresh if at the top of the page
        if (window.scrollY === 0) {
            startY.current = e.touches[0].clientY;
            setIsPulling(true);
        }
    }, []);

    const handleTouchMove = useCallback((e) => {
        if (!isPulling || isRefreshing) return;

        currentY.current = e.touches[0].clientY;
        const diff = (currentY.current - startY.current) / resistance;

        if (diff > 0) {
            setPullDistance(Math.min(diff, threshold * 1.5));

            // Prevent scrolling when pulling down
            if (diff > threshold * 0.3) {
                e.preventDefault();
            }
        }
    }, [isPulling, isRefreshing, threshold, resistance]);

    const handleTouchEnd = useCallback(async () => {
        if (pullDistance >= threshold && !isRefreshing && onRefresh) {
            setIsRefreshing(true);
            setPullDistance(threshold * 0.5); // Keep some visual feedback

            try {
                await onRefresh();
            } catch (error) {
                console.error('Refresh failed:', error);
            } finally {
                setIsRefreshing(false);
            }
        }

        setIsPulling(false);
        setPullDistance(0);
    }, [pullDistance, threshold, isRefreshing, onRefresh]);

    return {
        pullDistance,
        isRefreshing,
        isPulling,
        canRefresh: pullDistance >= threshold,
        handlers: {
            onTouchStart: handleTouchStart,
            onTouchMove: handleTouchMove,
            onTouchEnd: handleTouchEnd,
        },
    };
}

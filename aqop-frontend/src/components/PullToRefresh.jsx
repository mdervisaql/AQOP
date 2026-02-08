import { usePullToRefresh } from '../hooks/usePullToRefresh';
import { RefreshCw } from 'lucide-react';

/**
 * Pull to Refresh Component
 * 
 * Wrapper component that enables pull-to-refresh functionality.
 * Shows a visual indicator when pulling and a spinner while refreshing.
 * 
 * @param {Function} onRefresh - Async function to call when refresh is triggered
 * @param {React.ReactNode} children - Content to wrap
 */
export default function PullToRefresh({ onRefresh, children }) {
    const { pullDistance, isRefreshing, canRefresh, handlers } = usePullToRefresh(onRefresh);

    return (
        <div {...handlers} className="relative overflow-hidden">
            {/* Pull Indicator */}
            <div
                className={`
          absolute left-1/2 -translate-x-1/2 z-10
          flex items-center justify-center
          transition-opacity duration-200
          ${pullDistance > 10 || isRefreshing ? 'opacity-100' : 'opacity-0'}
        `}
                style={{
                    top: Math.min(pullDistance - 40, 24),
                }}
            >
                <div className={`
          w-10 h-10 rounded-full bg-white shadow-lg
          flex items-center justify-center
          transition-transform duration-200
          ${canRefresh ? 'scale-110' : 'scale-100'}
        `}>
                    {isRefreshing ? (
                        <RefreshCw className="w-5 h-5 text-blue-500 animate-spin" />
                    ) : (
                        <RefreshCw
                            className={`w-5 h-5 text-blue-500 transition-transform duration-200`}
                            style={{
                                transform: `rotate(${pullDistance * 3}deg)`,
                            }}
                        />
                    )}
                </div>
            </div>

            {/* Content with pull effect */}
            <div
                style={{
                    transform: `translateY(${isRefreshing ? 20 : pullDistance * 0.4}px)`,
                    transition: pullDistance === 0 && !isRefreshing ? 'transform 0.3s ease-out' : 'none'
                }}
            >
                {children}
            </div>
        </div>
    );
}

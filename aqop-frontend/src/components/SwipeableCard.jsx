import { useState, useRef } from 'react';
import { Phone, Archive } from 'lucide-react';

/**
 * Swipeable Card Component
 * 
 * Wraps content with swipe-to-reveal actions.
 * Swipe right to reveal left action (e.g., call)
 * Swipe left to reveal right action (e.g., archive)
 * 
 * @param {React.ReactNode} children - Card content
 * @param {Function} onSwipeLeft - Action when swiped left
 * @param {Function} onSwipeRight - Action when swiped right
 * @param {Object} leftAction - Left action config { label, icon, color }
 * @param {Object} rightAction - Right action config { label, icon, color }
 */
export default function SwipeableCard({
    children,
    onSwipeLeft,
    onSwipeRight,
    leftAction = { label: 'اتصال', icon: Phone, color: 'bg-green-500' },
    rightAction = { label: 'أرشفة', icon: Archive, color: 'bg-orange-500' },
}) {
    const [translateX, setTranslateX] = useState(0);
    const [isDragging, setIsDragging] = useState(false);
    const startX = useRef(0);
    const startTime = useRef(0);
    const cardRef = useRef(null);

    const handleTouchStart = (e) => {
        startX.current = e.touches[0].clientX;
        startTime.current = Date.now();
        setIsDragging(true);
    };

    const handleTouchMove = (e) => {
        if (!isDragging) return;

        const currentX = e.touches[0].clientX;
        const diff = currentX - startX.current;

        // Apply resistance at edges
        const resistedDiff = diff > 0
            ? Math.min(diff * 0.6, 100)
            : Math.max(diff * 0.6, -100);

        setTranslateX(resistedDiff);
    };

    const handleTouchEnd = () => {
        setIsDragging(false);

        const velocity = Math.abs(translateX) / (Date.now() - startTime.current);
        const threshold = velocity > 0.5 ? 40 : 60; // Lower threshold for fast swipes

        if (translateX > threshold && onSwipeRight) {
            // Swipe right - trigger left action (revealed on right swipe)
            onSwipeRight();
        } else if (translateX < -threshold && onSwipeLeft) {
            // Swipe left - trigger right action
            onSwipeLeft();
        }

        // Reset position
        setTranslateX(0);
    };

    const LeftIcon = leftAction.icon;
    const RightIcon = rightAction.icon;

    return (
        <div className="relative overflow-hidden rounded-xl mb-3">
            {/* Background Actions */}
            <div className="absolute inset-0 flex">
                {/* Left action (revealed on right swipe) */}
                <div className={`flex-1 ${leftAction.color} flex items-center justify-start pl-6`}>
                    <LeftIcon className="w-6 h-6 text-white" />
                    <span className="text-white font-medium ml-2">{leftAction.label}</span>
                </div>

                {/* Right action (revealed on left swipe) */}
                <div className={`flex-1 ${rightAction.color} flex items-center justify-end pr-6`}>
                    <span className="text-white font-medium mr-2">{rightAction.label}</span>
                    <RightIcon className="w-6 h-6 text-white" />
                </div>
            </div>

            {/* Card content */}
            <div
                ref={cardRef}
                onTouchStart={handleTouchStart}
                onTouchMove={handleTouchMove}
                onTouchEnd={handleTouchEnd}
                className="relative bg-white"
                style={{
                    transform: `translateX(${translateX}px)`,
                    transition: isDragging ? 'none' : 'transform 0.3s ease-out'
                }}
            >
                {children}
            </div>
        </div>
    );
}

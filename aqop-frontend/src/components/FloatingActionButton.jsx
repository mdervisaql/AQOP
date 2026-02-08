import { Plus } from 'lucide-react';

/**
 * Floating Action Button (FAB) Component
 * 
 * Mobile-only floating button for primary actions.
 * Positioned above the bottom navigation.
 * 
 * @param {Function} onClick - Click handler
 * @param {React.ReactNode} icon - Custom icon (default: Plus)
 * @param {string} label - Accessibility label
 * @param {string} className - Additional classes
 */
export default function FloatingActionButton({
    onClick,
    icon,
    label = 'Add',
    className = ''
}) {
    return (
        <button
            onClick={onClick}
            className={`
        lg:hidden fixed bottom-24 right-4 z-20
        w-14 h-14 rounded-full
        bg-blue-600 text-white
        shadow-lg shadow-blue-500/30
        flex items-center justify-center
        active:scale-95 active:bg-blue-700
        transition-all duration-150
        touch-manipulation
        ${className}
      `}
            aria-label={label}
        >
            {icon || <Plus className="w-6 h-6" strokeWidth={2.5} />}
        </button>
    );
}

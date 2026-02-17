import { createContext, useContext, useState, useCallback } from 'react';
import { CheckCircle, XCircle, Info, X } from 'lucide-react';

/**
 * Toast Notification Context and Provider
 * 
 * Provides toast notifications that appear above the bottom nav.
 * 
 * Usage:
 *   const { addToast } = useToast();
 *   addToast('Success!', 'success');
 */

const ToastContext = createContext(null);

export function ToastProvider({ children }) {
    const [toasts, setToasts] = useState([]);

    const addToast = useCallback((message, type = 'info', duration = 3000) => {
        const id = Date.now() + Math.random();
        setToasts(prev => [...prev, { id, message, type }]);

        // Auto-remove after duration
        setTimeout(() => {
            setToasts(prev => prev.filter(t => t.id !== id));
        }, duration);
    }, []);

    const removeToast = useCallback((id) => {
        setToasts(prev => prev.filter(t => t.id !== id));
    }, []);

    const getIcon = (type) => {
        switch (type) {
            case 'success':
                return <CheckCircle className="w-5 h-5" />;
            case 'error':
                return <XCircle className="w-5 h-5" />;
            default:
                return <Info className="w-5 h-5" />;
        }
    };

    const getColors = (type) => {
        switch (type) {
            case 'success':
                return 'bg-green-500 text-white';
            case 'error':
                return 'bg-red-500 text-white';
            default:
                return 'bg-blue-500 text-white';
        }
    };

    return (
        <ToastContext.Provider value={{ addToast, removeToast }}>
            {children}

            {/* Toast Container - positioned above bottom nav on mobile */}
            <div className="fixed bottom-20 lg:bottom-8 left-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
                {toasts.map(toast => (
                    <div
                        key={toast.id}
                        className={`
              animate-slide-up pointer-events-auto
              flex items-center gap-3 p-4 rounded-xl shadow-lg
              ${getColors(toast.type)}
            `}
                    >
                        {getIcon(toast.type)}
                        <span className="flex-1 font-medium">{toast.message}</span>
                        <button
                            onClick={() => removeToast(toast.id)}
                            className="p-1 hover:bg-white/20 rounded transition-colors"
                        >
                            <X className="w-4 h-4" />
                        </button>
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    const context = useContext(ToastContext);
    if (!context) {
        throw new Error('useToast must be used within a ToastProvider');
    }
    return context;
}

import React, { useState, useEffect } from 'react';
import { isPushSupported, requestPermission, subscribeToPush, unsubscribeFromPush, getSubscriptionState } from '../utils/pushNotifications';

export default function PushNotificationSettings() {
    const [isSupported, setIsSupported] = useState(false);
    const [permission, setPermission] = useState('default');
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        const checkStatus = async () => {
            const supported = isPushSupported();
            setIsSupported(supported);

            if (supported) {
                setPermission(Notification.permission);
                const subscription = await getSubscriptionState();
                setIsSubscribed(!!subscription);
            }
        };
        checkStatus();
    }, []);

    const handleEnable = async () => {
        setLoading(true);
        setError(null);
        try {
            const perm = await requestPermission();
            setPermission(perm);

            if (perm === 'granted') {
                await subscribeToPush();
                setIsSubscribed(true);
            } else {
                setError('Permission denied');
            }
        } catch (err) {
            console.error(err);
            setError('Failed to enable push notifications');
        } finally {
            setLoading(false);
        }
    };

    const handleDisable = async () => {
        setLoading(true);
        try {
            await unsubscribeFromPush();
            setIsSubscribed(false);
        } catch (err) {
            console.error(err);
            setError('Failed to disable push notifications');
        } finally {
            setLoading(false);
        }
    };

    if (!isSupported) {
        return (
            <div className="p-4 bg-gray-50 rounded-md">
                <p className="text-gray-500">Push notifications are not supported in this browser.</p>
            </div>
        );
    }

    return (
        <div className="p-6 bg-white shadow rounded-lg">
            <h3 className="text-lg font-medium leading-6 text-gray-900 mb-4">Browser Push Notifications</h3>

            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-gray-500">
                        Receive notifications even when the application is closed.
                    </p>
                    {error && <p className="text-sm text-red-600 mt-1">{error}</p>}
                </div>

                <div>
                    {isSubscribed ? (
                        <button
                            onClick={handleDisable}
                            disabled={loading}
                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            {loading ? 'Processing...' : 'Disable'}
                        </button>
                    ) : (
                        <button
                            onClick={handleEnable}
                            disabled={loading || permission === 'denied'}
                            className={`inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white ${permission === 'denied' ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500`}
                        >
                            {loading ? 'Processing...' : (permission === 'denied' ? 'Blocked' : 'Enable')}
                        </button>
                    )}
                </div>
            </div>

            {permission === 'denied' && (
                <p className="mt-2 text-sm text-red-600">
                    Notifications are blocked by your browser settings. Please enable them manually in your browser address bar.
                </p>
            )}
        </div>
    );
}

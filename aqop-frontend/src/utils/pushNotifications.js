import api from '../api';

/**
 * Check if Push Notifications are supported
 */
export function isPushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window;
}

/**
 * Request Notification Permission
 */
export async function requestPermission() {
    if (!isPushSupported()) {
        throw new Error('Push notifications are not supported');
    }

    const permission = await Notification.requestPermission();
    return permission;
}

/**
 * Subscribe to Push Notifications
 */
export async function subscribeToPush() {
    if (!isPushSupported()) {
        return null;
    }

    const registration = await navigator.serviceWorker.ready;

    // Get VAPID key from server
    const { data: { key } } = await api.get('/notifications/push/vapid-key');

    if (!key) {
        throw new Error('VAPID key not found');
    }

    const convertedVapidKey = urlBase64ToUint8Array(key);

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: convertedVapidKey
    });

    // Send subscription to server
    await api.post('/notifications/push/subscribe', subscription);

    return subscription;
}

/**
 * Unsubscribe from Push Notifications
 */
export async function unsubscribeFromPush() {
    if (!isPushSupported()) {
        return;
    }

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (subscription) {
        await subscription.unsubscribe();
        // Optionally notify server to remove subscription
    }
}

/**
 * Get current subscription state
 */
export async function getSubscriptionState() {
    if (!isPushSupported()) {
        return null;
    }

    const registration = await navigator.serviceWorker.ready;
    return await registration.pushManager.getSubscription();
}

/**
 * Helper: Convert VAPID key
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

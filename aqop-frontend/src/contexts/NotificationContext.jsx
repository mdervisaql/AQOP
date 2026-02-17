import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import api from '../api';
import { useAuth } from '../auth/AuthContext';

const NotificationContext = createContext();

export function useNotifications() {
    return useContext(NotificationContext);
}

export function NotificationProvider({ children }) {
    const { user } = useAuth();
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [loading, setLoading] = useState(false);

    const fetchNotifications = useCallback(async (page = 1) => {
        if (!user) return;
        setLoading(true);
        try {
            const response = await api.get(`/aqop/v1/notifications?page=${page}`);
            if (page === 1) {
                setNotifications(response.data);
            } else {
                setNotifications(prev => [...prev, ...response.data]);
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        } finally {
            setLoading(false);
        }
    }, [user]);

    const fetchUnreadCount = useCallback(async () => {
        if (!user) return;
        try {
            const response = await api.get('/aqop/v1/notifications/unread-count');
            setUnreadCount(response?.data?.count || 0);
        } catch (error) {
            console.error('Failed to fetch unread count:', error);
        }
    }, [user]);

    const markAsRead = async (id) => {
        try {
            await api.post(`/aqop/v1/notifications/${id}/read`);
            setNotifications(prev =>
                prev.map(n => n.id === id ? { ...n, is_read: true } : n)
            );
            setUnreadCount(prev => Math.max(0, prev - 1));
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    };

    const markAllAsRead = async () => {
        try {
            await api.post('/aqop/v1/notifications/mark-all-read');
            setNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
            setUnreadCount(0);
        } catch (error) {
            console.error('Failed to mark all as read:', error);
        }
    };

    // Poll for unread count
    useEffect(() => {
        if (!user) return;

        fetchUnreadCount();
        const interval = setInterval(fetchUnreadCount, 30000); // Poll every 30s

        return () => clearInterval(interval);
    }, [user, fetchUnreadCount]);

    const value = {
        notifications,
        unreadCount,
        loading,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead
    };

    return (
        <NotificationContext.Provider value={value}>
            {children}
        </NotificationContext.Provider>
    );
}

import React, { useState, useRef, useEffect } from 'react';
import { useNotifications } from '../contexts/NotificationContext';
import { Link } from 'react-router-dom';
import { formatDistanceToNow } from 'date-fns';
import { ar } from 'date-fns/locale';

export default function NotificationBell() {
    const { unreadCount, notifications, fetchNotifications, markAsRead, markAllAsRead } = useNotifications();
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    useEffect(() => {
        if (isOpen) {
            fetchNotifications(1);
        }
    }, [isOpen, fetchNotifications]);

    // Close dropdown when clicking outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, [dropdownRef]);

    const handleNotificationClick = (notification) => {
        if (!notification.is_read) {
            markAsRead(notification.id);
        }
        setIsOpen(false);
    };

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Bell Icon */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none"
            >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>

                {unreadCount > 0 && (
                    <span className="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {/* Dropdown */}
            {isOpen && (
                <div className="absolute left-0 z-50 w-80 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none rtl:left-0 rtl:right-auto ltr:right-0 ltr:left-auto">
                    <div className="py-1">
                        <div className="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                            <h3 className="text-sm font-semibold text-gray-900">Notifications</h3>
                            {unreadCount > 0 && (
                                <button
                                    onClick={markAllAsRead}
                                    className="text-xs text-blue-600 hover:text-blue-800"
                                >
                                    Mark all read
                                </button>
                            )}
                        </div>

                        <div className="max-h-96 overflow-y-auto">
                            {notifications.length > 0 ? (
                                notifications.slice(0, 5).map((notification) => (
                                    <div
                                        key={notification.id}
                                        className={`px-4 py-3 hover:bg-gray-50 border-b border-gray-50 last:border-0 ${!notification.is_read ? 'bg-blue-50' : ''}`}
                                    >
                                        <Link
                                            to={notification.action_url || '#'}
                                            onClick={() => handleNotificationClick(notification)}
                                            className="block"
                                        >
                                            <p className="text-sm font-medium text-gray-900">{notification.title}</p>
                                            <p className="text-xs text-gray-500 mt-1">{notification.message}</p>
                                            <p className="text-xs text-gray-400 mt-1">
                                                {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true, locale: ar })}
                                            </p>
                                        </Link>
                                    </div>
                                ))
                            ) : (
                                <div className="px-4 py-6 text-center text-gray-500 text-sm">
                                    No notifications
                                </div>
                            )}
                        </div>

                        <div className="px-4 py-2 border-t border-gray-100 bg-gray-50 text-center">
                            <Link
                                to="/notifications"
                                onClick={() => setIsOpen(false)}
                                className="text-sm text-blue-600 hover:text-blue-800 font-medium"
                            >
                                View All Notifications
                            </Link>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

/**
 * Notifications Page
 * 
 * Mobile-optimized notifications list with filter tabs.
 */

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navigation from '../components/Navigation';

export default function NotificationsPage() {
    const navigate = useNavigate();
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(false);
    const [filter, setFilter] = useState('all');

    // Mock data - replace with API call later
    const mockNotifications = [
        {
            id: 1,
            type: 'new_lead',
            title: 'ليد جديد',
            message: 'تم إضافة ليد جديد: أحمد محمد',
            time_ago: 'منذ 5 دقائق',
            read: false,
        },
        {
            id: 2,
            type: 'lead_assigned',
            title: 'تم تعيين ليد',
            message: 'تم تعيين ليد جديد لك',
            time_ago: 'منذ ساعة',
            read: false,
        },
        {
            id: 3,
            type: 'follow_up',
            title: 'تذكير متابعة',
            message: 'لديك متابعة مجدولة اليوم',
            time_ago: 'منذ ساعتين',
            read: true,
        },
        {
            id: 4,
            type: 'status_change',
            title: 'تغيير حالة',
            message: 'تم تحويل ليد إلى مؤهل',
            time_ago: 'منذ 3 ساعات',
            read: true,
        },
    ];

    useEffect(() => {
        setNotifications(mockNotifications);
    }, []);

    const unreadCount = notifications.filter(n => !n.read).length;

    const filteredNotifications = notifications.filter(n => {
        if (filter === 'unread') return !n.read;
        if (filter === 'read') return n.read;
        return true;
    });

    const markAsRead = (id) => {
        setNotifications(prev =>
            prev.map(n => n.id === id ? { ...n, read: true } : n)
        );
    };

    const markAllAsRead = () => {
        setNotifications(prev => prev.map(n => ({ ...n, read: true })));
    };

    const getIcon = (type) => {
        const icons = {
            'new_lead': '🆕',
            'lead_assigned': '👤',
            'lead_updated': '✏️',
            'follow_up': '⏰',
            'message': '💬',
            'status_change': '🔄',
            'system': 'ℹ️',
        };
        return icons[type] || '🔔';
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Desktop Sidebar */}
            <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
                <Navigation currentPage="notifications" />
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden">
                <Navigation currentPage="notifications" />
            </div>

            {/* Main Content */}
            <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
                {/* Header */}
                <div className="bg-white px-4 py-4 border-b sticky top-14 lg:top-0 z-10">
                    <div className="flex items-center justify-between">
                        <h1 className="text-xl font-bold text-gray-900">الإشعارات</h1>
                        {unreadCount > 0 && (
                            <button
                                onClick={markAllAsRead}
                                className="text-blue-600 text-sm font-medium"
                            >
                                قراءة الكل
                            </button>
                        )}
                    </div>

                    {/* Filter Tabs */}
                    <div className="flex gap-2 mt-3">
                        {[
                            { id: 'all', label: 'الكل' },
                            { id: 'unread', label: 'غير مقروء' },
                            { id: 'read', label: 'مقروء' },
                        ].map(f => (
                            <button
                                key={f.id}
                                onClick={() => setFilter(f.id)}
                                className={`
                  px-4 py-2 rounded-full text-sm font-medium flex items-center gap-1
                  ${filter === f.id
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 text-gray-600'
                                    }
                `}
                            >
                                {f.label}
                                {f.id === 'unread' && unreadCount > 0 && (
                                    <span className={`
                    min-w-[20px] h-5 px-1 rounded-full text-xs flex items-center justify-center
                    ${filter === f.id ? 'bg-white/20' : 'bg-blue-600 text-white'}
                  `}>
                                        {unreadCount}
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Notifications List */}
                <div className="px-4 py-4">
                    {loading ? (
                        <div className="text-center py-16">
                            <div className="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                            <p className="text-gray-500 mt-4">جاري التحميل...</p>
                        </div>
                    ) : filteredNotifications.length > 0 ? (
                        <div className="space-y-2">
                            {filteredNotifications.map(notification => (
                                <div
                                    key={notification.id}
                                    onClick={() => markAsRead(notification.id)}
                                    className={`
                    bg-white rounded-xl p-4 flex gap-3 active:bg-gray-50 cursor-pointer
                    ${!notification.read ? 'border-r-4 border-blue-500' : ''}
                  `}
                                >
                                    <div className="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-xl flex-shrink-0">
                                        {getIcon(notification.type)}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className={`text-sm ${!notification.read ? 'font-bold' : ''} text-gray-900`}>
                                            {notification.title}
                                        </p>
                                        <p className="text-xs text-gray-500 mt-1 line-clamp-2">
                                            {notification.message}
                                        </p>
                                        <p className="text-xs text-gray-400 mt-2">
                                            {notification.time_ago}
                                        </p>
                                    </div>
                                    {!notification.read && (
                                        <div className="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <span className="text-5xl">🔔</span>
                            <p className="text-gray-900 font-medium mt-4">لا توجد إشعارات</p>
                            <p className="text-gray-500 text-sm mt-1">ستظهر الإشعارات الجديدة هنا</p>
                        </div>
                    )}
                </div>
            </main>
        </div>
    );
}

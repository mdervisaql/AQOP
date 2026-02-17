/**
 * Profile Page
 * 
 * Mobile-optimized user profile with settings and logout.
 */

import { useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import Navigation from '../components/Navigation';

export default function ProfilePage() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    // Get role label in Arabic
    const getRoleLabel = (role) => {
        const labels = {
            'aq_agent': 'وكيل',
            'aq_supervisor': 'مشرف',
            'aq_country_manager': 'مدير الدولة',
            'operation_manager': 'مدير العمليات',
            'operation_admin': 'مدير النظام',
            'administrator': 'مدير النظام',
        };
        return labels[role] || role?.replace(/_/g, ' ');
    };

    const handleLogout = () => {
        if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
            logout();
            navigate('/login');
        }
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Desktop Sidebar */}
            <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
                <Navigation currentPage="profile" />
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden">
                <Navigation currentPage="profile" />
            </div>

            {/* Main Content */}
            <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
                {/* Profile Header */}
                <div className="bg-gradient-to-r from-slate-800 to-slate-900 text-white px-4 py-8 text-center">
                    <div className="w-20 h-20 bg-white/20 rounded-full mx-auto flex items-center justify-center text-3xl mb-3">
                        {user?.display_name?.charAt(0)?.toUpperCase() || '👤'}
                    </div>
                    <h1 className="text-xl font-bold">{user?.display_name || user?.username}</h1>
                    <p className="text-slate-300 text-sm mt-1">{user?.email}</p>
                    <span className="inline-block mt-2 px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-xs font-medium">
                        {getRoleLabel(user?.role)}
                    </span>
                </div>

                {/* Settings Menu */}
                <div className="px-4 mt-6 space-y-2">
                    <h2 className="text-sm font-medium text-gray-500 mb-3">الإعدادات</h2>

                    <MenuItem
                        icon="🔔"
                        label="إعدادات الإشعارات"
                        onClick={() => navigate('/notification-settings')}
                    />
                    <MenuItem
                        icon="🔒"
                        label="تغيير كلمة المرور"
                        onClick={() => { }}
                        disabled
                    />
                    <MenuItem
                        icon="🌐"
                        label="اللغة"
                        value="العربية"
                    />
                </div>

                {/* Support */}
                <div className="px-4 mt-6 space-y-2">
                    <h2 className="text-sm font-medium text-gray-500 mb-3">الدعم</h2>

                    <MenuItem
                        icon="❓"
                        label="مركز المساعدة"
                        onClick={() => { }}
                        disabled
                    />
                    <MenuItem
                        icon="💬"
                        label="تواصل معنا"
                        onClick={() => window.open('mailto:support@aqleads.com')}
                    />
                </div>

                {/* Account Info */}
                <div className="px-4 mt-6">
                    <h2 className="text-sm font-medium text-gray-500 mb-3">معلومات الحساب</h2>
                    <div className="bg-white rounded-xl p-4 space-y-3">
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">معرف المستخدم</span>
                            <span className="font-medium text-gray-900">#{user?.id}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">اسم المستخدم</span>
                            <span className="font-medium text-gray-900">{user?.username}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">الصلاحية</span>
                            <span className="font-medium text-gray-900">{getRoleLabel(user?.role)}</span>
                        </div>
                    </div>
                </div>

                {/* Logout Button */}
                <div className="px-4 mt-8 mb-6">
                    <button
                        onClick={handleLogout}
                        className="w-full py-3 bg-red-50 text-red-600 rounded-xl font-medium active:bg-red-100 transition-colors"
                    >
                        تسجيل الخروج
                    </button>
                </div>

                {/* App Version */}
                <p className="text-center text-xs text-gray-400 mb-6">
                    AQOP Platform v2.0.0
                </p>
            </main>
        </div>
    );
}

// Menu Item Component
function MenuItem({ icon, label, onClick, value, disabled }) {
    return (
        <button
            onClick={disabled ? undefined : onClick}
            disabled={disabled}
            className={`
        w-full bg-white rounded-xl p-4 flex items-center justify-between 
        ${disabled ? 'opacity-50' : 'active:bg-gray-50'}
        transition-colors
      `}
        >
            <div className="flex items-center gap-3">
                <span className="text-xl">{icon}</span>
                <span className="font-medium text-gray-900">{label}</span>
            </div>
            {value ? (
                <span className="text-sm text-gray-500">{value}</span>
            ) : (
                <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
            )}
        </button>
    );
}

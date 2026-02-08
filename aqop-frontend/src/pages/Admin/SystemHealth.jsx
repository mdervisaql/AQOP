import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../auth/AuthContext';
import { useSystemHealth, useSystemStats } from '../../hooks/useSystem';
import Navigation from '../../components/Navigation';
import BottomNav from '../../components/BottomNav';
import LoadingSpinner from '../../components/LoadingSpinner';
import {
    Activity,
    Database,
    Server,
    CheckCircle,
    AlertTriangle,
    XCircle,
    RefreshCw,
    Send,
    HardDrive,
    Table
} from 'lucide-react';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    BarChart,
    Bar
} from 'recharts';

const StatusBadge = ({ status, message }) => {
    const colors = {
        ok: 'bg-green-100 text-green-800 border-green-200',
        error: 'bg-red-100 text-red-800 border-red-200',
        warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    };

    const icons = {
        ok: <CheckCircle className="w-4 h-4 mr-1" />,
        error: <XCircle className="w-4 h-4 mr-1" />,
        warning: <AlertTriangle className="w-4 h-4 mr-1" />,
    };

    return (
        <div className={`flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${colors[status] || colors.warning}`}>
            {icons[status] || icons.warning}
            {message || status}
        </div>
    );
};

const IntegrationCard = ({ name, icon: Icon, data }) => {
    if (!data) return null;

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center space-x-3">
                    <div className="p-2 bg-blue-50 rounded-lg">
                        <Icon className="w-6 h-6 text-blue-600" />
                    </div>
                    <h3 className="font-semibold text-gray-900">{name}</h3>
                </div>
                <StatusBadge status={data.status} message={data.message} />
            </div>
            <div className="text-xs text-gray-500 flex justify-between items-center mt-4 pt-4 border-t border-gray-50">
                <span>آخر فحص:</span>
                <span className="font-mono text-left" dir="ltr">{data.last_checked || 'Never'}</span>
            </div>
        </div>
    );
};

export default function SystemHealth() {
    const navigate = useNavigate();
    const { user } = useAuth();
    const [days, setDays] = useState(7);

    const { data: health, isLoading: healthLoading, refetch: refetchHealth } = useSystemHealth();
    const { data: stats, isLoading: statsLoading } = useSystemStats(days);

    if (healthLoading || statsLoading) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <LoadingSpinner size="lg" text="جاري تحليل حالة النظام..." />
            </div>
        );
    }

    const chartData = stats?.daily_trends?.map(item => ({
        date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        count: parseInt(item.count),
        type: item.event_name
    })) || [];

    return (
        <div className="min-h-screen bg-gray-50 bg-slate-50">
            {/* Sidebar */}
            <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
                <Navigation currentPage="system-health" />
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden">
                <Navigation currentPage="system-health" />
            </div>

            {/* Main Content */}
            <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
                {/* Mobile Header */}
                <div className="lg:hidden bg-white px-4 py-3 border-b sticky top-14 z-10 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => navigate(-1)}
                            className="p-2 hover:bg-gray-100 rounded-lg"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h1 className="text-lg font-bold text-slate-900">صحة النظام</h1>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Header */}
                    <div className="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 tracking-tight">صحة النظام</h1>
                            <p className="mt-2 text-gray-600">المراقبة الفورية وحالة التكامل</p>
                        </div>
                        <button
                            onClick={() => refetchHealth()}
                            className="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            <RefreshCw className="w-4 h-4 mr-2" />
                            تحديث الحالة
                        </button>
                    </div>

                    {/* Core Metrics */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        {/* Database */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-green-50 rounded-full opacity-50 blur-xl"></div>
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">زمن استجابة قاعدة البيانات</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1" dir="ltr">{health?.database?.latency}ms</p>
                                </div>
                                <div className={`p-2 rounded-lg ${health?.database?.status === 'ok' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
                                    <Database className="w-6 h-6" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-sm">
                                <StatusBadge status={health?.database?.status} message={health?.database?.message} />
                            </div>
                        </div>

                        {/* Async Queue */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-blue-50 rounded-full opacity-50 blur-xl"></div>
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">المهام غير المتزامنة</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">{health?.queue?.count}</p>
                                </div>
                                <div className="p-2 bg-blue-100 text-blue-600 rounded-lg">
                                    <Server className="w-6 h-6" />
                                </div>
                            </div>
                            <div className="mt-4 text-sm text-gray-600">
                                المهام الخلفية المعلقة
                            </div>
                        </div>

                        {/* Events Today */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-purple-50 rounded-full opacity-50 blur-xl"></div>
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">أحداث اليوم</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">{stats?.events_today}</p>
                                </div>
                                <div className="p-2 bg-purple-100 text-purple-600 rounded-lg">
                                    <Activity className="w-6 h-6" />
                                </div>
                            </div>
                            <div className="mt-4 text-sm text-green-600 flex items-center">
                                <span className="flex h-2 w-2 rounded-full bg-green-500 mr-2"></span>
                                النظام نشط
                            </div>
                        </div>

                        {/* Error Rate */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-red-50 rounded-full opacity-50 blur-xl"></div>
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">الأخطاء (24 ساعة)</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">{stats?.errors_24h}</p>
                                </div>
                                <div className={`p-2 rounded-lg ${stats?.errors_24h > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}`}>
                                    <AlertTriangle className="w-6 h-6" />
                                </div>
                            </div>
                            <div className="mt-4 text-sm text-gray-600">
                                المشاكل الحرجة التي تتطلب الانتباه
                            </div>
                        </div>
                    </div>

                    {/* Integrations Status */}
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">حالة التكامل</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <IntegrationCard
                            name="Airtable Sync"
                            icon={Table}
                            data={health?.airtable}
                        />
                        <IntegrationCard
                            name="Telegram Bot"
                            icon={Send}
                            data={health?.telegram}
                        />
                        <IntegrationCard
                            name="Dropbox Storage"
                            icon={HardDrive}
                            data={health?.dropbox}
                        />
                    </div>

                    {/* Analytics Charts */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Event Volume Chart */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-semibold text-gray-900">نشاط النظام</h3>
                                <select
                                    value={days}
                                    onChange={(e) => setDays(parseInt(e.target.value))}
                                    className="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white"
                                >
                                    <option value={7}>آخر 7 أيام</option>
                                    <option value={14}>آخر 14 يوم</option>
                                    <option value={30}>آخر 30 يوم</option>
                                </select>
                            </div>
                            <div className="h-80">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={chartData}>
                                        <defs>
                                            <linearGradient id="colorCount" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#3B82F6" stopOpacity={0.1} />
                                                <stop offset="95%" stopColor="#3B82F6" stopOpacity={0} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E5E7EB" />
                                        <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fill: '#6B7280', fontSize: 12 }} dy={10} />
                                        <YAxis axisLine={false} tickLine={false} tick={{ fill: '#6B7280', fontSize: 12 }} />
                                        <Tooltip
                                            contentStyle={{ backgroundColor: '#fff', borderRadius: '8px', border: '1px solid #E5E7EB', boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)' }}
                                            itemStyle={{ color: '#111827', fontWeight: 600 }}
                                        />
                                        <Area type="monotone" dataKey="count" stroke="#3B82F6" strokeWidth={2} fillOpacity={1} fill="url(#colorCount)" />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Event Distribution */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-6">توزيع الأحداث</h3>
                            <div className="h-80">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} layout="vertical">
                                        <CartesianGrid strokeDasharray="3 3" horizontal={true} vertical={false} stroke="#E5E7EB" />
                                        <XAxis type="number" hide />
                                        <YAxis dataKey="type" type="category" width={150} tick={{ fill: '#4B5563', fontSize: 12 }} />
                                        <Tooltip cursor={{ fill: 'transparent' }} />
                                        <Bar dataKey="count" fill="#8B5CF6" radius={[0, 4, 4, 0]} barSize={20} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {/* Bottom Navigation for Mobile */}
            <BottomNav />
        </div>
    );
}

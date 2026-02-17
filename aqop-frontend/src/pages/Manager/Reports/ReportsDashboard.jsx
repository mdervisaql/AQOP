import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Calendar, Filter, Download } from 'lucide-react';
import Navigation from '../../../components/Navigation';
import BottomNav from '../../../components/BottomNav';
import AgentPerformanceReport from './AgentPerformanceReport';
import SourcesReport from './SourcesReport';
import CampaignsReport from './CampaignsReport';
import TimeAnalysisReport from './TimeAnalysisReport';
import StatusReport from './StatusReport';
import CountryReport from './CountryReport';
import { getSummaryReport } from '../../../api/reports';

const ReportsDashboard = () => {
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('performance');
    const [dateRange, setDateRange] = useState({
        from: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
        to: new Date().toISOString().split('T')[0]
    });
    const [summary, setSummary] = useState({ total_leads: 0, converted_leads: 0, conversion_rate: 0, avg_score: 0 });

    useEffect(() => {
        fetchSummary();
    }, [dateRange]);

    const fetchSummary = async () => {
        try {
            const response = await getSummaryReport(dateRange);
            setSummary(response.data);
        } catch (err) {
            console.error('Failed to fetch summary stats', err);
        }
    };

    const handleDateChange = (e) => {
        const { name, value } = e.target;
        setDateRange(prev => ({ ...prev, [name]: value }));
    };

    const tabs = [
        { id: 'performance', label: 'أداء الوكلاء' },
        { id: 'sources', label: 'المصادر' },
        { id: 'campaigns', label: 'الحملات' },
        { id: 'time', label: 'التحليل الزمني' },
        { id: 'status', label: 'توزيع الحالات' },
        { id: 'country', label: 'تحليل الدول' },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Sidebar */}
            <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
                <Navigation currentPage="reports" />
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden">
                <Navigation currentPage="reports" />
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
                        <h1 className="text-lg font-bold text-gray-900">التقارير والتحليلات</h1>
                    </div>
                </div>

                <div className="p-4 lg:p-6 max-w-7xl mx-auto space-y-6">
                    {/* Header & Filters */}
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div className="hidden lg:block">
                            <h1 className="text-2xl font-bold text-slate-900">التقارير والتحليلات</h1>
                        </div>

                        <div className="w-full md:w-auto flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
                            <Calendar size={18} className="text-slate-500 ml-2" />
                            <input
                                type="date"
                                name="from"
                                value={dateRange.from}
                                onChange={handleDateChange}
                                className="border-none text-sm focus:ring-0 text-slate-700 bg-transparent"
                            />
                            <span className="text-slate-400">-</span>
                            <input
                                type="date"
                                name="to"
                                value={dateRange.to}
                                onChange={handleDateChange}
                                className="border-none text-sm focus:ring-0 text-slate-700 bg-transparent"
                            />
                        </div>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="bg-white p-4 lg:p-6 rounded-lg shadow border border-slate-200">
                            <p className="text-xs lg:text-sm font-medium text-slate-500">إجمالي الليدات</p>
                            <p className="text-xl lg:text-3xl font-bold text-slate-900 mt-2">{summary.total_leads}</p>
                        </div>
                        <div className="bg-white p-4 lg:p-6 rounded-lg shadow border border-slate-200">
                            <p className="text-xs lg:text-sm font-medium text-slate-500">الليدات المحولة</p>
                            <p className="text-xl lg:text-3xl font-bold text-green-600 mt-2">{summary.converted_leads}</p>
                        </div>
                        <div className="bg-white p-4 lg:p-6 rounded-lg shadow border border-slate-200">
                            <p className="text-xs lg:text-sm font-medium text-slate-500">معدل التحويل</p>
                            <p className="text-xl lg:text-3xl font-bold text-indigo-600 mt-2">{summary.conversion_rate}%</p>
                        </div>
                        <div className="bg-white p-4 lg:p-6 rounded-lg shadow border border-slate-200">
                            <p className="text-xs lg:text-sm font-medium text-slate-500">جودة الليدات</p>
                            <p className="text-xl lg:text-3xl font-bold text-orange-500 mt-2">{summary.avg_score}</p>
                        </div>
                    </div>

                    {/* Tabs */}
                    <div className="border-b border-slate-200 overflow-x-auto">
                        <nav className="-mb-px flex space-x-6 lg:space-x-8 min-w-max" aria-label="Tabs">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`
                                        whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors
                                        ${activeTab === tab.id
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'}
                                    `}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </nav>
                    </div>

                    {/* Report Content */}
                    <div className="min-h-[400px]">
                        {activeTab === 'performance' && <AgentPerformanceReport dateRange={dateRange} />}
                        {activeTab === 'sources' && <SourcesReport dateRange={dateRange} />}
                        {activeTab === 'campaigns' && <CampaignsReport dateRange={dateRange} />}
                        {activeTab === 'time' && <TimeAnalysisReport dateRange={dateRange} />}
                        {activeTab === 'status' && <StatusReport dateRange={dateRange} />}
                        {activeTab === 'country' && <CountryReport dateRange={dateRange} />}
                    </div>
                </div>
            </main>

            {/* Bottom Navigation for Mobile */}
            <BottomNav />
        </div>
    );
};

export default ReportsDashboard;

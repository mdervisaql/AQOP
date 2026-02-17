/**
 * Enhanced Analytics Page (Manager View)
 *
 * Comprehensive analytics with interactive charts, advanced metrics, and agent performance breakdown.
 */

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
  BarChart, Bar, PieChart, Pie, Cell, AreaChart, Area
} from 'recharts';
import Navigation from '../../components/Navigation';
import BottomNav from '../../components/BottomNav';
import apiClient from '../../api/index';
import { getLeadsStats, getLeads } from '../../api/leads';
import { getAqopUsers } from '../../api/users';

// Add new detailed analytics API
const getDetailedAnalytics = async (params = {}) => {
  const queryString = new URLSearchParams(params).toString();
  const endpoint = queryString ? `/aqop/v1/analytics/detailed?${queryString}` : '/aqop/v1/analytics/detailed';
  return await apiClient.get(endpoint);
};
import LoadingSpinner from '../../components/LoadingSpinner';

export default function Analytics() {
  const navigate = useNavigate();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [timeRange, setTimeRange] = useState('30days');
  const [customDateRange, setCustomDateRange] = useState({ start: '', end: '' });
  const [topPerformers, setTopPerformers] = useState([]);
  const [allAgents, setAllAgents] = useState([]);
  const [agentPerformance, setAgentPerformance] = useState([]);
  const [chartData, setChartData] = useState({});
  const [sortConfig, setSortConfig] = useState({ key: 'conversionRate', direction: 'desc' });

  // Time range options
  const timeRangeOptions = [
    { value: 'today', label: 'اليوم' },
    { value: 'yesterday', label: 'أمس' },
    { value: '7days', label: 'آخر 7 أيام' },
    { value: '30days', label: 'آخر 30 يوم' },
    { value: 'thisweek', label: 'هذا الأسبوع' },
    { value: 'lastweek', label: 'الأسبوع الماضي' },
    { value: 'thismonth', label: 'هذا الشهر' },
    { value: 'lastmonth', label: 'الشهر الماضي' },
    { value: 'thisquarter', label: 'هذا الربع' },
    { value: 'lastquarter', label: 'الربع الماضي' },
    { value: 'thisyear', label: 'هذا العام' },
    { value: 'custom', label: 'فترة مخصصة' },
  ];

  useEffect(() => {
    fetchAnalytics();
  }, [timeRange, customDateRange]);

  const fetchAnalytics = async () => {
    setLoading(true);

    try {
      // Use the new detailed analytics API
      const params = {
        time_range: timeRange,
      };

      if (timeRange === 'custom' && customDateRange.start && customDateRange.end) {
        params.start_date = customDateRange.start;
        params.end_date = customDateRange.end;
      }

      const detailedResponse = await getDetailedAnalytics(params);

      if (detailedResponse.success && detailedResponse.data) {
        const analytics = detailedResponse.data;

        // Set agent performance data
        setAgentPerformance(analytics.agent_performance || []);

        // Generate chart data from API response
        setChartData({
          timeSeries: analytics.time_trends || [],
          statusPie: analytics.status_distribution || [],
          sourcePie: analytics.source_breakdown || [],
          agentComparison: analytics.agent_performance?.slice(0, 5) || [],
        });

        // Calculate top performers from agent performance data
        calculateTopPerformersFromAPI(analytics.agent_performance || []);
      }

      // Still fetch basic stats for the key metrics cards
      const statsResponse = await getLeadsStats();
      if (statsResponse.success && statsResponse.data) {
        setStats(statsResponse.data);
      }
    } catch (err) {
      console.error('Error fetching analytics:', err);
    } finally {
      setLoading(false);
    }
  };

  const calculateTopPerformersFromAPI = (agentData) => {
    // Sort by conversion rate for top performers
    const sortedAgents = [...agentData].sort((a, b) => b.conversion_rate - a.conversion_rate);

    const performers = sortedAgents.slice(0, 10).map(agent => ({
      name: agent.name,
      total: agent.total_leads,
      converted: agent.converted,
      conversionRate: `${agent.conversion_rate}%`,
      contactRate: `${agent.contact_rate}%`,
    }));

    setTopPerformers(performers);
  };


  const getConversionRate = () => {
    if (!stats || stats.total_leads === 0) return 0;
    return ((stats.converted_leads / stats.total_leads) * 100).toFixed(1);
  };

  const getContactRate = () => {
    if (!stats || stats.total_leads === 0) return 0;
    const contacted = stats.contacted_leads + stats.qualified_leads + stats.converted_leads;
    return ((contacted / stats.total_leads) * 100).toFixed(1);
  };

  const getQualificationRate = () => {
    if (!stats || stats.total_leads === 0) return 0;
    const qualified = stats.qualified_leads + stats.converted_leads;
    return ((qualified / stats.total_leads) * 100).toFixed(1);
  };

  const getMonthlyGrowthRate = () => {
    // Placeholder - would need historical data
    return '+12.5%';
  };

  const getAverageResponseTime = () => {
    // Placeholder - would need actual timing data
    return '2.3 hours';
  };

  const getAverageDealSize = () => {
    // Placeholder - would need revenue data
    return '$2,450';
  };

  const handleSort = (key) => {
    let direction = 'asc';
    if (sortConfig.key === key && sortConfig.direction === 'asc') {
      direction = 'desc';
    }
    setSortConfig({ key, direction });
  };

  const getSortedAgentPerformance = () => {
    const sortableItems = [...agentPerformance];
    sortableItems.sort((a, b) => {
      if (a[sortConfig.key] < b[sortConfig.key]) {
        return sortConfig.direction === 'asc' ? -1 : 1;
      }
      if (a[sortConfig.key] > b[sortConfig.key]) {
        return sortConfig.direction === 'asc' ? 1 : -1;
      }
      return 0;
    });
    return sortableItems;
  };

  const exportAgentReport = () => {
    const csvContent = [
      ['Agent Name', 'Total Leads', 'Contacted', 'Qualified', 'Converted', 'Conversion Rate', 'Contact Rate', 'This Month', 'This Month Rate'],
      ...getSortedAgentPerformance().map(agent => [
        agent.name,
        agent.totalLeads,
        agent.contacted,
        agent.qualified,
        agent.converted,
        `${agent.conversionRate}%`,
        `${agent.contactRate}%`,
        agent.thisMonth,
        `${agent.thisMonthRate}%`
      ])
    ].map(row => row.join(',')).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `agent-performance-report-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  };

  // Chart colors
  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

  const getTimeRangeLabel = () => {
    const option = timeRangeOptions.find(opt => opt.value === timeRange);
    return option ? option.label : timeRange;
  };


  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <LoadingSpinner size="lg" text="جاري تحميل التحليلات..." />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Desktop Sidebar */}
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="analytics" />
      </div>

      {/* Mobile Navigation */}
      <div className="lg:hidden">
        <Navigation currentPage="analytics" />
      </div>

      {/* Main Content */}
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
        {/* Mobile Compact Header */}
        <div className="lg:hidden bg-slate-900 text-white px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <button
                onClick={() => navigate(-1)}
                className="p-2 -ml-2 hover:bg-slate-800 rounded-lg transition"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <div>
                <h1 className="text-xl font-bold">لوحة التحليلات</h1>
                <p className="text-slate-400 text-xs mt-1">مقاييس الأداء والتحليلات</p>
              </div>
            </div>
            <select
              value={timeRange}
              onChange={(e) => setTimeRange(e.target.value)}
              className="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white"
            >
              {timeRangeOptions.map(option => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Mobile Stats Grid */}
        <div className="lg:hidden px-4 py-4 grid grid-cols-2 gap-3">
          {/* Total Leads */}
          <div className="bg-white rounded-xl p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs text-gray-500">إجمالي الليدات</p>
              <span className="text-blue-600 text-lg">👥</span>
            </div>
            <p className="text-2xl font-bold text-gray-900 mt-2">{stats?.total_leads || 0}</p>
            <p className="text-[10px] text-gray-400 mt-1">جميع الليدات</p>
          </div>

          {/* Conversion Rate */}
          <div className="bg-white rounded-xl p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs text-gray-500">معدل التحويل</p>
              <span className="text-green-600 text-lg">✅</span>
            </div>
            <p className="text-2xl font-bold text-gray-900 mt-2">{getConversionRate()}%</p>
            <p className="text-[10px] text-gray-400 mt-1">{stats?.converted_leads || 0} محوّل</p>
          </div>

          {/* Contact Rate */}
          <div className="bg-white rounded-xl p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs text-gray-500">معدل التواصل</p>
              <span className="text-purple-600 text-lg">💬</span>
            </div>
            <p className="text-2xl font-bold text-gray-900 mt-2">{getContactRate()}%</p>
            <p className="text-[10px] text-gray-400 mt-1">{stats?.contacted_leads || 0} تم التواصل</p>
          </div>

          {/* Qualification Rate */}
          <div className="bg-white rounded-xl p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs text-gray-500">معدل التأهيل</p>
              <span className="text-yellow-600 text-lg">⭐</span>
            </div>
            <p className="text-2xl font-bold text-gray-900 mt-2">{getQualificationRate()}%</p>
            <p className="text-[10px] text-gray-400 mt-1">{stats?.qualified_leads || 0} مؤهل</p>
          </div>

          {/* Pending Leads */}
          <div className="bg-white rounded-xl p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs text-gray-500">المعلق</p>
              <span className="text-orange-600 text-lg">⏳</span>
            </div>
            <p className="text-2xl font-bold text-gray-900 mt-2">{stats?.pending_leads || 0}</p>
            <p className="text-[10px] text-gray-400 mt-1">بانتظار المتابعة</p>
          </div>

          {/* Monthly Growth */}
          <div className="bg-white rounded-xl p-4">
            <div className="flex items-center justify-between">
              <p className="text-xs text-gray-500">النمو الشهري</p>
              <span className="text-emerald-600 text-lg">📈</span>
            </div>
            <p className="text-2xl font-bold text-green-600 mt-2">{getMonthlyGrowthRate()}</p>
            <p className="text-[10px] text-gray-400 mt-1">زيادة الليدات</p>
          </div>
        </div>

        {/* Desktop Layout */}
        <div className="hidden lg:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Desktop Header */}
          <div className="mb-8">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">لوحة التحليلات</h1>
                <p className="mt-2 text-gray-600">مقاييس الأداء والتحليلات التفاعلية</p>
              </div>
              <div className="flex items-center gap-4">
                <div>
                  <label htmlFor="timeRange" className="block text-sm font-medium text-gray-700 mb-1">
                    الفترة الزمنية
                  </label>
                  <select
                    id="timeRange"
                    value={timeRange}
                    onChange={(e) => setTimeRange(e.target.value)}
                    className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                  >
                    {timeRangeOptions.map(option => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                </div>

                {timeRange === 'custom' && (
                  <div className="flex gap-2">
                    <div>
                      <label htmlFor="startDate" className="block text-sm font-medium text-gray-700 mb-1">
                        من
                      </label>
                      <input
                        type="date"
                        id="startDate"
                        value={customDateRange.start}
                        onChange={(e) => setCustomDateRange({ ...customDateRange, start: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                      />
                    </div>
                    <div>
                      <label htmlFor="endDate" className="block text-sm font-medium text-gray-700 mb-1">
                        إلى
                      </label>
                      <input
                        type="date"
                        id="endDate"
                        value={customDateRange.end}
                        onChange={(e) => setCustomDateRange({ ...customDateRange, end: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                      />
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Desktop Key Metrics */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
            {/* Total Leads */}
            <div className="bg-white rounded-lg shadow p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-500">إجمالي الليدات</h3>
                <span className="text-blue-600 text-xl">👥</span>
              </div>
              <p className="text-2xl font-bold text-gray-900">{stats?.total_leads || 0}</p>
              <p className="text-xs text-gray-500">جميع الليدات في النظام</p>
            </div>

            {/* Conversion Rate */}
            <div className="bg-white rounded-lg shadow p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-500">معدل التحويل</h3>
                <span className="text-green-600 text-xl">✅</span>
              </div>
              <p className="text-2xl font-bold text-gray-900">{getConversionRate()}%</p>
              <p className="text-xs text-gray-500">{stats?.converted_leads || 0} محوّل</p>
            </div>

            {/* Contact Rate */}
            <div className="bg-white rounded-lg shadow p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-500">معدل التواصل</h3>
                <span className="text-purple-600 text-xl">💬</span>
              </div>
              <p className="text-2xl font-bold text-gray-900">{getContactRate()}%</p>
              <p className="text-xs text-gray-500">تم التواصل بنجاح</p>
            </div>

            {/* Qualification Rate */}
            <div className="bg-white rounded-lg shadow p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-500">معدل التأهيل</h3>
                <span className="text-orange-600 text-xl">⭐</span>
              </div>
              <p className="text-2xl font-bold text-gray-900">{getQualificationRate()}%</p>
              <p className="text-xs text-gray-500">ليدات مؤهلة</p>
            </div>

            {/* Average Response Time */}
            <div className="bg-white rounded-lg shadow p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-500">متوسط وقت الرد</h3>
                <span className="text-indigo-600 text-xl">⏱️</span>
              </div>
              <p className="text-2xl font-bold text-gray-900">{getAverageResponseTime()}</p>
              <p className="text-xs text-gray-500">وقت أول تواصل</p>
            </div>

            {/* Monthly Growth */}
            <div className="bg-white rounded-lg shadow p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-500">النمو الشهري</h3>
                <span className="text-emerald-600 text-xl">📈</span>
              </div>
              <p className="text-2xl font-bold text-green-600">{getMonthlyGrowthRate()}</p>
              <p className="text-xs text-gray-500">زيادة في الليدات</p>
            </div>
          </div>
        </div>

        {/* Interactive Charts Section */}
        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
          {/* Leads Over Time */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Leads Over Time</h3>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={chartData.timeSeries || []}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="date" />
                  <YAxis />
                  <Tooltip />
                  <Legend />
                  <Area type="monotone" dataKey="total" stackId="1" stroke="#8884d8" fill="#8884d8" name="Total Leads" />
                  <Area type="monotone" dataKey="converted" stackId="2" stroke="#82ca9d" fill="#82ca9d" name="Converted" />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Leads by Status */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Leads by Status</h3>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={chartData.statusPie || []}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="name" />
                  <YAxis />
                  <Tooltip />
                  <Bar dataKey="value" fill="#8884d8">
                    {(chartData.statusPie || []).map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Leads by Source */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Leads by Source</h3>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={chartData.sourcePie || []}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    label={({ name, percentage }) => `${name}: ${percentage}%`}
                    outerRadius={80}
                    fill="#8884d8"
                    dataKey="value"
                  >
                    {(chartData.sourcePie || []).map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Agent Comparison */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Agents Performance</h3>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={chartData.agentComparison || []} layout="horizontal">
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis type="number" />
                  <YAxis dataKey="name" type="category" width={80} />
                  <Tooltip />
                  <Legend />
                  <Bar dataKey="total" fill="#8884d8" name="Total Leads" />
                  <Bar dataKey="converted" fill="#82ca9d" name="Converted" />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        {/* Conversion Trends Chart */}
        <div className="bg-white rounded-lg shadow p-6 mb-8">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Conversion Trends</h3>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={chartData.timeSeries || []}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis />
                <Tooltip />
                <Legend />
                <Line type="monotone" dataKey="total" stroke="#8884d8" strokeWidth={2} name="Total Leads" />
                <Line type="monotone" dataKey="converted" stroke="#82ca9d" strokeWidth={2} name="Converted Leads" />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Comprehensive Agent Performance Breakdown */}
        <div className="bg-white rounded-lg shadow p-6 mb-8">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-xl font-semibold text-gray-900">Agent Performance Breakdown</h2>
              <p className="text-sm text-gray-600 mt-1">Detailed performance metrics for all agents</p>
            </div>
            <button
              onClick={exportAgentReport}
              className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export CSV
            </button>
          </div>

          {agentPerformance.length === 0 ? (
            <p className="text-gray-500 text-center py-8">No agent performance data available</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Agent
                    </th>
                    <th
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                      onClick={() => handleSort('total_leads')}
                    >
                      Total Leads
                      {sortConfig.key === 'total_leads' && (
                        <span className="ml-1">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Contacted
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Qualified
                    </th>
                    <th
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                      onClick={() => handleSort('converted')}
                    >
                      Converted
                      {sortConfig.key === 'converted' && (
                        <span className="ml-1">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </th>
                    <th
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                      onClick={() => handleSort('conversion_rate')}
                    >
                      Conv. Rate
                      {sortConfig.key === 'conversion_rate' && (
                        <span className="ml-1">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </th>
                    <th
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                      onClick={() => handleSort('contact_rate')}
                    >
                      Contact Rate
                      {sortConfig.key === 'contact_rate' && (
                        <span className="ml-1">{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Period Leads
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Period Rate
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {getSortedAgentPerformance().map((agent, index) => (
                    <tr key={agent.name} className={index < 3 ? 'bg-yellow-50' : ''}>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="flex-shrink-0 h-10 w-10">
                            <div className="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                              <span className="text-sm font-medium text-white">
                                {agent.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)}
                              </span>
                            </div>
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-medium text-gray-900">{agent.name}</div>
                            {index < 3 && (
                              <div className="text-xs text-yellow-600 font-medium">
                                Top Performer #{index + 1}
                              </div>
                            )}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-semibold text-gray-900">{agent.total_leads}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">{agent.contacted}</div>
                        <div className="text-xs text-gray-500">
                          {agent.total_leads > 0 ? ((agent.contacted / agent.total_leads) * 100).toFixed(1) : 0}% of total
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-orange-600 font-medium">{agent.qualified}</div>
                        <div className="text-xs text-gray-500">
                          {agent.total_leads > 0 ? ((agent.qualified / agent.total_leads) * 100).toFixed(1) : 0}% of total
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-semibold text-green-600">{agent.converted}</div>
                        <div className="text-xs text-gray-500">
                          {agent.total_leads > 0 ? ((agent.converted / agent.total_leads) * 100).toFixed(1) : 0}% of total
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-2xl font-bold text-green-600">{agent.conversion_rate}%</div>
                        <div className="w-full bg-gray-200 rounded-full h-2 mt-1">
                          <div
                            className="bg-green-500 h-2 rounded-full"
                            style={{ width: `${Math.min(agent.conversion_rate, 100)}%` }}
                          ></div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-2xl font-bold text-blue-600">{agent.contact_rate}%</div>
                        <div className="w-full bg-gray-200 rounded-full h-2 mt-1">
                          <div
                            className="bg-blue-500 h-2 rounded-full"
                            style={{ width: `${Math.min(agent.contact_rate, 100)}%` }}
                          ></div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{agent.period_leads}</div>
                        <div className="text-xs text-gray-500">leads in period</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-semibold text-purple-600">{agent.period_rate}%</div>
                        <div className="text-xs text-gray-500">conversion rate</div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Summary Stats */}
          <div className="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 border-t border-gray-200">
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">{agentPerformance.length}</div>
              <div className="text-sm text-gray-500">Active Agents</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">
                {agentPerformance.length > 0 ?
                  (agentPerformance.reduce((sum, agent) => sum + agent.conversion_rate, 0) / agentPerformance.length).toFixed(1)
                  : 0}%
              </div>
              <div className="text-sm text-gray-500">متوسط التحويل</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-purple-600">
                {agentPerformance.reduce((sum, agent) => sum + agent.total_leads, 0)}
              </div>
              <div className="text-sm text-gray-500">إجمالي الليدات المعينة</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-orange-600">
                {agentPerformance.reduce((sum, agent) => sum + agent.converted, 0)}
              </div>
              <div className="text-sm text-gray-500">إجمالي التحويلات</div>
            </div>
          </div>
        </div>
      </main>

      {/* Bottom Navigation for Mobile */}
      <BottomNav />
    </div>
  );
}

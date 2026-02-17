/**
 * Team Leads Page (Supervisor View)
 * 
 * Display team leads with assignment capabilities.
 * Mobile-optimized with collapsible filters and Arabic labels.
 */

import { useState, useEffect } from 'react';
import { useAuth } from '../../auth/AuthContext';
import { getLeads, updateLead, getCountries } from '../../api/leads';
import { getAgents } from '../../api/users';
import Navigation from '../../components/Navigation';
import LeadCard from '../../components/LeadCard';
import LoadingSpinner from '../../components/LoadingSpinner';

export default function TeamLeads() {
  const { user } = useAuth();
  const [leads, setLeads] = useState([]);
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filtersOpen, setFiltersOpen] = useState(false);

  const [countries, setCountries] = useState([]);

  // Filters
  const [filters, setFilters] = useState({
    status: '',
    priority: '',
    search: '',
    assigned_to: '',
    country: '',
  });

  // Bulk actions
  const [selectedLeads, setSelectedLeads] = useState([]);
  const [bulkAction, setBulkAction] = useState('');
  const [bulkAssignTo, setBulkAssignTo] = useState('');
  const [processingBulk, setProcessingBulk] = useState(false);

  useEffect(() => {
    fetchCountries();
  }, []);

  useEffect(() => {
    fetchData();
  }, [filters]);

  const fetchCountries = async () => {
    try {
      const countriesData = await getCountries();
      let allCountries = countriesData?.data || [];

      // Filter countries based on user's assigned countries
      const userCountryIds = user?.country_ids;
      if (userCountryIds && userCountryIds.length > 0) {
        allCountries = allCountries.filter(c => userCountryIds.includes(parseInt(c.id)));
      }

      setCountries(allCountries);
    } catch (err) {
      console.error('Error fetching countries:', err);
    }
  };

  const fetchData = async () => {
    setLoading(true);
    setError(null);

    try {
      const params = {};
      if (filters.status) params.status = filters.status;
      if (filters.priority) params.priority = filters.priority;
      if (filters.search) params.search = filters.search;
      if (filters.assigned_to) params.assigned_to = filters.assigned_to;
      if (filters.country) params.country = filters.country;

      const response = await getLeads(params);

      if (response.success && response.data) {
        setLeads(response.data.results || []);
      } else {
        setLeads([]);
      }

      const agentsData = await getAgents();
      setAgents(agentsData || []);
    } catch (err) {
      console.error('Error fetching data:', err);
      setError(err.message || 'فشل تحميل الليدات');
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (filterName, value) => {
    setFilters(prev => ({
      ...prev,
      [filterName]: value,
    }));
  };

  const clearFilters = () => {
    setFilters({
      status: '',
      priority: '',
      search: '',
      assigned_to: '',
      country: '',
    });
  };

  const hasActiveFilters = filters.status || filters.priority || filters.search || filters.assigned_to || filters.country;

  const toggleSelectLead = (leadId) => {
    setSelectedLeads(prev => {
      if (prev.includes(leadId)) {
        return prev.filter(id => id !== leadId);
      } else {
        return [...prev, leadId];
      }
    });
  };

  const toggleSelectAll = () => {
    if (selectedLeads.length === leads.length) {
      setSelectedLeads([]);
    } else {
      setSelectedLeads(leads.map(lead => lead.id));
    }
  };

  const handleBulkAction = async () => {
    if (!bulkAction || selectedLeads.length === 0) return;

    setProcessingBulk(true);

    try {
      const updates = [];

      for (const leadId of selectedLeads) {
        const updateData = {};

        if (bulkAction === 'assign' && bulkAssignTo) {
          updateData.assigned_to = parseInt(bulkAssignTo);
        } else if (bulkAction.startsWith('status_')) {
          const statusCode = bulkAction.replace('status_', '');
          updateData.status_code = statusCode;
        }

        if (Object.keys(updateData).length > 0) {
          updates.push(updateLead(leadId, updateData));
        }
      }

      await Promise.all(updates);
      await fetchData();

      setSelectedLeads([]);
      setBulkAction('');
      setBulkAssignTo('');

      alert(`تم تحديث ${selectedLeads.length} ليد بنجاح!`);
    } catch (err) {
      console.error('Bulk action error:', err);
      alert('فشلت بعض التحديثات. يرجى المحاولة مرة أخرى.');
    } finally {
      setProcessingBulk(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Desktop Sidebar */}
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="team-leads" />
      </div>

      {/* Mobile Navigation */}
      <div className="lg:hidden">
        <Navigation currentPage="team-leads" />
      </div>

      {/* Main Content */}
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-0">
        {/* Mobile Sticky Header */}
        <div className="lg:hidden bg-white px-4 py-3 border-b sticky top-14 z-10">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold text-gray-900">ليدات الفريق</h1>
              <p className="text-xs text-gray-500">{leads.length} ليد</p>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={() => setFiltersOpen(!filtersOpen)}
                className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${filtersOpen ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'
                  }`}
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                فلترة
                {hasActiveFilters && <span className="w-2 h-2 bg-blue-500 rounded-full"></span>}
              </button>
              <button onClick={fetchData} className="p-2 bg-gray-100 rounded-lg">
                <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Collapsible Filters - Mobile */}
        {filtersOpen && (
          <div className="lg:hidden bg-white border-b px-4 py-4 space-y-4 animate-slide-down">
            <input
              type="search"
              value={filters.search}
              onChange={(e) => handleFilterChange('search', e.target.value)}
              placeholder="البحث في ليدات الفريق..."
              className="w-full h-11 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm"
            />

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs text-gray-500 mb-1 block">الدولة</label>
                <select
                  value={filters.country}
                  onChange={(e) => handleFilterChange('country', e.target.value)}
                  className="w-full h-10 px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"
                >
                  <option value="">كل الدول</option>
                  {countries.map(country => (
                    <option key={country.id} value={country.id}>
                      {country.country_name_ar || country.country_name_en}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs text-gray-500 mb-1 block">الحالة</label>
                <select
                  value={filters.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="w-full h-10 px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"
                >
                  <option value="">كل الحالات</option>
                  <option value="pending">معلق</option>
                  <option value="contacted">تم التواصل</option>
                  <option value="qualified">مؤهل</option>
                  <option value="converted">محول</option>
                  <option value="lost">خسارة</option>
                </select>
              </div>

              <div>
                <label className="text-xs text-gray-500 mb-1 block">الأولوية</label>
                <select
                  value={filters.priority}
                  onChange={(e) => handleFilterChange('priority', e.target.value)}
                  className="w-full h-10 px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"
                >
                  <option value="">كل الأولويات</option>
                  <option value="low">منخفض</option>
                  <option value="medium">متوسط</option>
                  <option value="high">عالي</option>
                  <option value="urgent">عاجل</option>
                </select>
              </div>

              <div>
                <label className="text-xs text-gray-500 mb-1 block">الوكيل</label>
                <select
                  value={filters.assigned_to}
                  onChange={(e) => handleFilterChange('assigned_to', e.target.value)}
                  className="w-full h-10 px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"
                >
                  <option value="">كل الوكلاء</option>
                  {agents.map(agent => (
                    <option key={agent.id} value={agent.id}>
                      {agent.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="flex gap-2 pt-2">
              <button
                onClick={() => setFiltersOpen(false)}
                className="flex-1 h-10 bg-blue-600 text-white rounded-lg font-medium text-sm"
              >
                تطبيق
              </button>
              <button
                onClick={clearFilters}
                className="px-4 h-10 bg-gray-100 text-gray-700 rounded-lg font-medium text-sm"
              >
                مسح
              </button>
            </div>
          </div>
        )}

        {/* Main Content Area */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 lg:py-8">
          {/* Desktop Header */}
          <div className="hidden lg:block mb-8">
            <h1 className="text-3xl font-bold text-gray-900">Team Leads</h1>
            <p className="mt-2 text-gray-600">
              Manage and assign team leads - {user?.display_name}
            </p>
          </div>

          {/* Desktop Filters */}
          <div className="hidden lg:block bg-white rounded-lg shadow p-6 mb-6">
            <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  placeholder="Search leads..."
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select
                  value={filters.country}
                  onChange={(e) => handleFilterChange('country', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Countries</option>
                  {countries.map(country => (
                    <option key={country.id} value={country.id}>
                      {country.country_name_en}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  value={filters.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Statuses</option>
                  <option value="pending">Pending</option>
                  <option value="contacted">Contacted</option>
                  <option value="qualified">Qualified</option>
                  <option value="converted">Converted</option>
                  <option value="lost">Lost</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select
                  value={filters.priority}
                  onChange={(e) => handleFilterChange('priority', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Priorities</option>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div className="flex items-end">
                <button
                  onClick={clearFilters}
                  className="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                  Clear Filters
                </button>
              </div>
            </div>
          </div>

          {/* Bulk Actions Bar */}
          {selectedLeads.length > 0 && (
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex flex-wrap items-center gap-4">
                  <span className="text-sm font-medium text-blue-900">
                    {selectedLeads.length} ليد محدد
                  </span>
                  <select
                    value={bulkAction}
                    onChange={(e) => setBulkAction(e.target.value)}
                    className="px-3 py-1 border border-blue-300 rounded-md text-sm"
                  >
                    <option value="">اختر إجراء</option>
                    <option value="assign">تعيين إلى...</option>
                    <option value="status_pending">تغيير → معلق</option>
                    <option value="status_contacted">تغيير → تم التواصل</option>
                    <option value="status_qualified">تغيير → مؤهل</option>
                    <option value="status_converted">تغيير → محول</option>
                    <option value="status_lost">تغيير → خسارة</option>
                  </select>

                  {bulkAction === 'assign' && (
                    <select
                      value={bulkAssignTo}
                      onChange={(e) => setBulkAssignTo(e.target.value)}
                      className="px-3 py-1 border border-blue-300 rounded-md text-sm"
                    >
                      <option value="">اختر وكيل</option>
                      {agents.map(agent => (
                        <option key={agent.id} value={agent.id}>
                          {agent.name}
                        </option>
                      ))}
                    </select>
                  )}

                  <button
                    onClick={handleBulkAction}
                    disabled={processingBulk || !bulkAction || (bulkAction === 'assign' && !bulkAssignTo)}
                    className="px-4 py-1 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                  >
                    {processingBulk ? 'جاري...' : 'تطبيق'}
                  </button>
                </div>

                <button
                  onClick={() => setSelectedLeads([])}
                  className="text-sm text-blue-600 hover:text-blue-800"
                >
                  إلغاء التحديد
                </button>
              </div>
            </div>
          )}

          {/* Loading State */}
          {loading && (
            <div className="flex justify-center py-12">
              <LoadingSpinner size="lg" text="جاري تحميل ليدات الفريق..." />
            </div>
          )}

          {/* Error State */}
          {error && !loading && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
              <p className="text-red-800">{error}</p>
            </div>
          )}

          {/* Empty State */}
          {!loading && !error && leads.length === 0 && (
            <div className="text-center py-12 bg-white rounded-lg shadow">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">لا توجد ليدات</h3>
              <p className="mt-1 text-sm text-gray-500">
                {hasActiveFilters ? 'جرب تعديل الفلاتر' : 'لا توجد ليدات للفريق'}
              </p>
            </div>
          )}

          {/* Leads List */}
          {!loading && !error && leads.length > 0 && (
            <>
              <div className="mb-4 flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <input
                    type="checkbox"
                    checked={selectedLeads.length === leads.length}
                    onChange={toggleSelectAll}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <p className="text-sm text-gray-600">
                    عرض <span className="font-medium">{leads.length}</span> ليد
                  </p>
                </div>
                <button
                  onClick={fetchData}
                  className="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  تحديث
                </button>
              </div>

              <div className="space-y-4">
                {leads.map((lead) => (
                  <div key={lead.id} className="flex items-start gap-3">
                    <input
                      type="checkbox"
                      checked={selectedLeads.includes(lead.id)}
                      onChange={() => toggleSelectLead(lead.id)}
                      className="mt-6 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <div className="flex-1">
                      <LeadCard lead={lead} showAssignee={true} />
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </div>
      </main>
    </div>
  );
}

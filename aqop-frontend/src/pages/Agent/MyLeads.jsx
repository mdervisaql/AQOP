/**
 * My Leads Page (Agent View)
 * 
 * Display leads assigned to the current agent.
 */

import { useState } from 'react';
import { useAuth } from '../../auth/AuthContext';
import { useLeads } from '../../hooks/useLeads';
import Navigation from '../../components/Navigation';
import LeadCard from '../../components/LeadCard';
import LoadingSpinner from '../../components/LoadingSpinner';
import BulkWhatsAppModal from '../../components/BulkWhatsAppModal';

export default function MyLeads() {
  const { user } = useAuth();

  // Mobile filter toggle
  const [filtersOpen, setFiltersOpen] = useState(false);

  const [filters, setFilters] = useState({
    status: '',
    priority: '',
    rating: '',
    search: '',
    sortBy: 'created_at_desc',
  });

  // Derive API params
  const apiParams = {
    ...filters,
    orderby: filters.sortBy === 'score_desc' || filters.sortBy === 'score_asc' ? 'lead_score' : 'created_at',
    order: filters.sortBy === 'score_asc' ? 'ASC' : 'DESC',
  };

  // React Query Hook (isAgent = true)
  const { data: leadsResponse, isLoading, error, refetch } = useLeads(apiParams, true, {
    refetchInterval: 60000,
  });
  const leads = leadsResponse?.data?.results || [];

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
      rating: '',
      search: '',
      sortBy: 'created_at_desc',
    });
  };

  // Bulk selection
  const [selectedLeads, setSelectedLeads] = useState([]);
  const [showWhatsAppModal, setShowWhatsAppModal] = useState(false);

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

  // Check if any filters are active
  const hasActiveFilters = filters.status || filters.priority || filters.rating || filters.search;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Desktop Sidebar */}
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="my-leads" />
      </div>

      {/* Mobile Navigation */}
      <div className="lg:hidden">
        <Navigation currentPage="my-leads" />
      </div>

      {/* Main Content */}
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-0">
        {/* Mobile Sticky Header */}
        <div className="lg:hidden bg-white px-4 py-3 border-b sticky top-14 z-10">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold text-gray-900">ليداتي</h1>
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
              <button onClick={() => refetch()} className="p-2 bg-gray-100 rounded-lg">
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
              placeholder="البحث في الليدات..."
              className="w-full h-11 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm"
            />

            <div className="grid grid-cols-2 gap-3">
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
                <label className="text-xs text-gray-500 mb-1 block">التقييم</label>
                <select
                  value={filters.rating}
                  onChange={(e) => handleFilterChange('rating', e.target.value)}
                  className="w-full h-10 px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"
                >
                  <option value="">كل التقييمات</option>
                  <option value="Hot">ساخن 🔥</option>
                  <option value="Warm">دافئ ☀️</option>
                  <option value="Cold">بارد ❄️</option>
                </select>
              </div>

              <div>
                <label className="text-xs text-gray-500 mb-1 block">الترتيب</label>
                <select
                  value={filters.sortBy}
                  onChange={(e) => handleFilterChange('sortBy', e.target.value)}
                  className="w-full h-10 px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"
                >
                  <option value="created_at_desc">الأحدث</option>
                  <option value="created_at_asc">الأقدم</option>
                  <option value="score_desc">التقييم ↓</option>
                  <option value="score_asc">التقييم ↑</option>
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
            <h1 className="text-3xl font-bold text-gray-900">My Leads</h1>
            <p className="mt-2 text-gray-600">
              Manage your assigned leads - {user?.display_name}
            </p>
          </div>

          {/* Desktop Filters */}
          <div className="hidden lg:block bg-white rounded-lg shadow p-6 mb-6">
            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
              <div>
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
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                <select
                  value={filters.rating}
                  onChange={(e) => handleFilterChange('rating', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Ratings</option>
                  <option value="Hot">Hot 🔥</option>
                  <option value="Warm">Warm ☀️</option>
                  <option value="Cold">Cold ❄️</option>
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
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <span className="text-sm font-medium text-blue-900">
                    {selectedLeads.length} lead{selectedLeads.length !== 1 ? 's' : ''} selected
                  </span>
                  <button
                    onClick={() => setShowWhatsAppModal(true)}
                    className="px-4 py-1 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700"
                  >
                    Send WhatsApp
                  </button>
                </div>
                <button
                  onClick={() => setSelectedLeads([])}
                  className="text-sm text-blue-600 hover:text-blue-800"
                >
                  Clear Selection
                </button>
              </div>
            </div>
          )}

          {/* Loading State */}
          {isLoading && (
            <div className="flex justify-center py-12">
              <LoadingSpinner size="lg" text="Loading your leads..." />
            </div>
          )}

          {/* Error State */}
          {error && !isLoading && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
              <p className="text-red-800">{error.message || 'Failed to load leads'}</p>
            </div>
          )}

          {/* Empty State */}
          {!isLoading && !error && leads.length === 0 && (
            <div className="text-center py-12 bg-white rounded-lg shadow">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">No leads found</h3>
              <p className="mt-1 text-sm text-gray-500">
                {hasActiveFilters ? 'Try adjusting your filters' : 'No leads assigned to you yet'}
              </p>
            </div>
          )}

          {/* Leads List */}
          {!isLoading && !error && leads.length > 0 && (
            <>
              {/* Results Header */}
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
                  onClick={() => refetch()}
                  className="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  تحديث
                </button>
              </div>

              {/* Lead Cards */}
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
                      <LeadCard lead={lead} showAssignee={false} />
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </div>
      </main>

      <BulkWhatsAppModal
        isOpen={showWhatsAppModal}
        onClose={() => setShowWhatsAppModal(false)}
        selectedLeads={selectedLeads}
        onSuccess={() => {
          setSelectedLeads([]);
          setShowWhatsAppModal(false);
          alert('Bulk WhatsApp job created successfully!');
        }}
      />
    </div>
  );
}

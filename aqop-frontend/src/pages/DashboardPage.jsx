/**
 * Dashboard Page
 * 
 * Main dashboard view for authenticated users.
 * Shows different content based on user role.
 */

import { useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { useLeads, useLeadsStats } from '../hooks/useLeads';
import { hasAnyRole } from '../utils/helpers';
import { ROLES } from '../utils/constants';
import Navigation from '../components/Navigation';
import LeadCard from '../components/LeadCard';
import LoadingSpinner from '../components/LoadingSpinner';
import NotificationBell from '../components/NotificationBell';
import FollowUpWidget from '../components/FollowUpWidget';

export default function DashboardPage() {
  const { user } = useAuth();
  const navigate = useNavigate();

  const isAgent = hasAnyRole(user, [ROLES.AGENT]);
  const isSupervisor = hasAnyRole(user, [ROLES.SUPERVISOR]);
  const isManager = hasAnyRole(user, [ROLES.ADMIN, ROLES.OPERATION_ADMIN, ROLES.OPERATION_MANAGER]);
  const isAdminOrManager = isManager || isSupervisor;

  // Fetch Statistics
  const { data: statsResponse, isLoading: statsLoading } = useLeadsStats({
    refetchInterval: 30000,
  });
  const stats = statsResponse?.data;

  // Fetch Recent Leads
  const { data: leadsResponse, isLoading: leadsLoading } = useLeads(
    {
      per_page: 5,
      orderby: 'created_at',
      order: 'DESC',
      limit: 5
    },
    !isManager && !isSupervisor,
    { refetchInterval: 60000 }
  );

  const recentLeads = leadsResponse?.data?.results || [];
  const loading = statsLoading || leadsLoading;

  // Get Arabic date
  const arabicDate = new Date().toLocaleDateString('ar-SA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Desktop Sidebar */}
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="dashboard" />
      </div>

      {/* Mobile Navigation */}
      <div className="lg:hidden">
        <Navigation currentPage="dashboard" />
      </div>

      {/* Main Content */}
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
        {/* Mobile Welcome Header */}
        <div className="lg:hidden bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-6">
          <h1 className="text-xl font-bold">مرحباً، {user?.display_name || user?.username} 👋</h1>
          <p className="text-blue-100 text-sm mt-1">{arabicDate}</p>
        </div>

        {/* Mobile Quick Stats - Horizontal Scroll */}
        <div className="lg:hidden px-4 -mt-4">
          <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            <StatCard
              icon="👥"
              label="إجمالي الليدات"
              value={stats?.total_leads || 0}
              color="bg-blue-500"
            />
            <StatCard
              icon="⏳"
              label="معلق"
              value={stats?.pending_leads || 0}
              color="bg-amber-500"
            />
            <StatCard
              icon="📞"
              label="تم التواصل"
              value={stats?.contacted_leads || 0}
              color="bg-sky-500"
            />
            <StatCard
              icon="✅"
              label="محوّل"
              value={stats?.converted_leads || 0}
              color="bg-emerald-500"
            />
          </div>
        </div>

        {/* Mobile Quick Actions */}
        <div className="lg:hidden px-4 mt-6">
          <h2 className="text-lg font-bold text-gray-900 mb-3">إجراءات سريعة</h2>
          <div className="grid grid-cols-2 gap-3">
            <button
              onClick={() => navigate('/leads')}
              className="bg-blue-50 text-blue-600 rounded-xl p-4 flex flex-col items-center justify-center min-h-[100px] active:scale-95 transition-transform"
            >
              <span className="text-3xl mb-2">📋</span>
              <span className="font-medium text-sm">ليداتي</span>
            </button>
            {isAdminOrManager && (
              <button
                onClick={() => navigate('/manager/analytics')}
                className="bg-green-50 text-green-600 rounded-xl p-4 flex flex-col items-center justify-center min-h-[100px] active:scale-95 transition-transform"
              >
                <span className="text-3xl mb-2">📊</span>
                <span className="font-medium text-sm">التحليلات</span>
              </button>
            )}
            {isSupervisor && (
              <button
                onClick={() => navigate('/supervisor/team-leads')}
                className="bg-purple-50 text-purple-600 rounded-xl p-4 flex flex-col items-center justify-center min-h-[100px] active:scale-95 transition-transform"
              >
                <span className="text-3xl mb-2">👥</span>
                <span className="font-medium text-sm">الفريق</span>
              </button>
            )}
            <button
              onClick={() => navigate('/notifications')}
              className="bg-gray-50 text-gray-600 rounded-xl p-4 flex flex-col items-center justify-center min-h-[100px] active:scale-95 transition-transform"
            >
              <span className="text-3xl mb-2">🔔</span>
              <span className="font-medium text-sm">الإشعارات</span>
            </button>
          </div>
        </div>

        {/* Mobile Recent Leads */}
        <div className="lg:hidden px-4 mt-6">
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-lg font-bold text-gray-900">آخر الليدات</h2>
            <button
              onClick={() => navigate('/leads')}
              className="text-blue-600 text-sm font-medium"
            >
              عرض الكل ←
            </button>
          </div>

          {loading ? (
            <div className="flex justify-center py-8">
              <LoadingSpinner size="md" />
            </div>
          ) : recentLeads.length > 0 ? (
            <div className="space-y-3">
              {recentLeads.slice(0, 5).map(lead => (
                <div
                  key={lead.id}
                  onClick={() => navigate(`/leads/${lead.id}`)}
                  className="bg-white rounded-xl p-3 flex items-center justify-between active:bg-gray-50 cursor-pointer"
                >
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 truncate">{lead.name}</p>
                    <p className="text-xs text-gray-500">{lead.phone || lead.email}</p>
                  </div>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ml-2 ${lead.status_code === 'pending' ? 'bg-amber-100 text-amber-700' :
                      lead.status_code === 'contacted' ? 'bg-blue-100 text-blue-700' :
                        lead.status_code === 'qualified' ? 'bg-purple-100 text-purple-700' :
                          lead.status_code === 'converted' ? 'bg-green-100 text-green-700' :
                            'bg-gray-100 text-gray-700'
                    }`}>
                    {lead.status_name_ar || lead.status_name_en}
                  </span>
                </div>
              ))}
            </div>
          ) : (
            <div className="bg-white rounded-xl p-6 text-center">
              <span className="text-4xl">📭</span>
              <p className="text-gray-500 mt-2">لا توجد ليدات</p>
            </div>
          )}
        </div>

        {/* Mobile Follow-ups Widget */}
        <div className="lg:hidden px-4 mt-6 mb-6">
          <FollowUpWidget />
        </div>

        {/* Desktop Layout */}
        <div className="hidden lg:block max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
          <div className="px-4 py-6 sm:px-0">
            {/* Desktop Welcome Section */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
              <div className="flex items-center justify-between">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 mb-2">
                    Welcome back, {user?.display_name || user?.username}!
                  </h2>
                  <p className="text-gray-600">
                    Role: <strong className="text-indigo-600 font-medium">{user?.role?.replace('_', ' ')}</strong>
                  </p>
                </div>
                <div className="flex items-center gap-4">
                  <NotificationBell />
                  <div className="flex items-center bg-emerald-50 px-4 py-2 rounded-full border border-emerald-100">
                    <span className="relative flex h-3 w-3 mr-2">
                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span className="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span className="text-sm font-medium text-emerald-700">Live Updates Active</span>
                  </div>
                </div>
              </div>
            </div>

            {loading ? (
              <div className="flex justify-center py-12">
                <LoadingSpinner size="lg" text="Loading dashboard..." />
              </div>
            ) : (
              <>
                {/* Desktop Statistics Cards */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                  <div className="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 transition-all hover:shadow-md">
                    <div className="p-6">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 p-3 bg-indigo-50 rounded-lg">
                          <svg className="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                          </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                          <dl>
                            <dt className="text-sm font-medium text-gray-500 truncate">
                              {isAgent ? 'My Leads' : 'Total Leads'}
                            </dt>
                            <dd className="text-2xl font-bold text-gray-900 mt-1">
                              {stats?.total_leads || 0}
                            </dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 transition-all hover:shadow-md">
                    <div className="p-6">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 p-3 bg-amber-50 rounded-lg">
                          <svg className="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                          <dl>
                            <dt className="text-sm font-medium text-gray-500 truncate">Pending</dt>
                            <dd className="text-2xl font-bold text-gray-900 mt-1">{stats?.pending_leads || 0}</dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 transition-all hover:shadow-md">
                    <div className="p-6">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 p-3 bg-blue-50 rounded-lg">
                          <svg className="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                          </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                          <dl>
                            <dt className="text-sm font-medium text-gray-500 truncate">Contacted</dt>
                            <dd className="text-2xl font-bold text-gray-900 mt-1">{stats?.contacted_leads || 0}</dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 transition-all hover:shadow-md">
                    <div className="p-6">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 p-3 bg-emerald-50 rounded-lg">
                          <svg className="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                          <dl>
                            <dt className="text-sm font-medium text-gray-500 truncate">Converted</dt>
                            <dd className="text-2xl font-bold text-gray-900 mt-1">{stats?.converted_leads || 0}</dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                  {/* Recent Leads Section */}
                  <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div className="flex items-center justify-between mb-6">
                      <h3 className="text-lg font-bold text-gray-900">
                        {isManager ? 'Recent Leads' : isSupervisor ? 'Team Recent Leads' : 'My Recent Leads'}
                      </h3>
                      <button
                        onClick={() => {
                          if (isManager) navigate('/manager/all-leads');
                          else if (isSupervisor) navigate('/supervisor/team-leads');
                          else navigate('/leads');
                        }}
                        className="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1"
                      >
                        View All <span aria-hidden="true">&rarr;</span>
                      </button>
                    </div>

                    {recentLeads.length === 0 ? (
                      <div className="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                        <div className="bg-white p-3 rounded-full inline-flex mb-4 shadow-sm">
                          <svg className="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                          </svg>
                        </div>
                        <p className="text-base font-medium text-gray-900">No leads assigned yet</p>
                        {isAgent && (
                          <p className="mt-1 text-sm text-gray-500">
                            Contact your manager to get leads assigned
                          </p>
                        )}
                      </div>
                    ) : (
                      <div className="space-y-4">
                        {recentLeads.map((lead) => (
                          <LeadCard key={lead.id} lead={lead} showAssignee={isAdminOrManager} />
                        ))}
                      </div>
                    )}
                  </div>

                  {/* Follow-up Widget */}
                  <div className="lg:col-span-1">
                    <FollowUpWidget />
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}

// Mobile Stat Card Component
function StatCard({ icon, label, value, color }) {
  return (
    <div className="bg-white rounded-xl shadow-sm p-4 min-w-[140px] flex-shrink-0">
      <div className={`w-10 h-10 ${color} rounded-lg flex items-center justify-center text-white text-xl mb-2`}>
        {icon}
      </div>
      <p className="text-2xl font-bold text-gray-900">{value}</p>
      <p className="text-xs text-gray-500">{label}</p>
    </div>
  );
}

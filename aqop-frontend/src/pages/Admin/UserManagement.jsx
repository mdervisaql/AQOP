/**
 * User Management Page (Admin Only)
 * 
 * Manage AQOP platform users - create, edit, delete users with proper roles.
 */

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../auth/AuthContext';
import { getAqopUsers, createUser, updateUser, deleteUser } from '../../api/users';
import { getCountries } from '../../api/leads';
import { ROLES } from '../../utils/constants';
import { getRoleDisplayName } from '../../utils/roleHelpers';
import { formatDateTime } from '../../utils/helpers';
import Navigation from '../../components/Navigation';
import BottomNav from '../../components/BottomNav';
import LoadingSpinner from '../../components/LoadingSpinner';
import {
  UserVisually,
  Plus,
  Search,
  Filter,
  Edit2,
  Trash2,
  CheckCircle,
  X,
  User
} from 'lucide-react';

export default function UserManagement() {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [countries, setCountries] = useState([]);
  const [loadingCountries, setLoadingCountries] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');

  // Modal state
  const [showModal, setShowModal] = useState(false);
  const [modalMode, setModalMode] = useState('create'); // 'create' or 'edit'
  const [editingUser, setEditingUser] = useState(null);

  // Form state
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    password: '',
    display_name: '',
    role: 'aq_agent',
    country_id: '',
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Delete confirmation
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const aqopRoles = [
    { value: 'aq_agent', label: 'وكيل (AQ Agent)' },
    { value: 'aq_supervisor', label: 'مشرف (AQ Supervisor)' },
    { value: 'aq_country_manager', label: 'مدير دولة (Country Manager)' },
    { value: 'operation_manager', label: 'مدير عمليات (Operation Manager)' },
    { value: 'operation_admin', label: 'مسؤول عمليات (Operation Admin)' },
  ];

  useEffect(() => {
    fetchUsers();
    fetchCountries();
  }, [roleFilter]);

  const fetchCountries = async () => {
    try {
      const response = await getCountries();
      if (response.success) {
        setCountries(response.data);
      }
    } catch (err) {
      console.error('Error fetching countries:', err);
    } finally {
      setLoadingCountries(false);
    }
  };

  const fetchUsers = async () => {
    setLoading(true);
    setError(null);

    try {
      const params = {};
      if (roleFilter) params.role = roleFilter;

      const response = await getAqopUsers(params);

      if (response.success && response.data) {
        setUsers(response.data);
      } else {
        setUsers([]);
      }
    } catch (err) {
      console.error('Error fetching users:', err);
      setError(err.message || 'فشل تحميل المستخدمين');
    } finally {
      setLoading(false);
    }
  };

  const filteredUsers = users.filter(user => {
    if (!search) return true;
    const searchLower = search.toLowerCase();
    return (
      user.username?.toLowerCase().includes(searchLower) ||
      user.email?.toLowerCase().includes(searchLower) ||
      user.display_name?.toLowerCase().includes(searchLower)
    );
  });

  const openCreateModal = () => {
    setModalMode('create');
    setEditingUser(null);
    setFormData({
      username: '',
      email: '',
      password: '',
      display_name: '',
      role: 'aq_agent',
      country_id: '',
    });
    setFormErrors({});
    setShowModal(true);
  };

  const openEditModal = (user) => {
    setModalMode('edit');
    setEditingUser(user);
    setFormData({
      username: user.username,
      email: user.email,
      password: '', // Don't pre-fill password
      display_name: user.display_name || '',
      role: user.role || 'aq_agent',
      country_id: user.country_id || '',
    });
    setFormErrors({});
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setEditingUser(null);
    setFormData({
      username: '',
      email: '',
      password: '',
      display_name: '',
      role: 'aq_agent',
      country_id: '',
    });
    setFormErrors({});
  };

  const validateForm = () => {
    const errors = {};

    if (modalMode === 'create') {
      if (!formData.username.trim()) {
        errors.username = 'اسم المستخدم مطلوب';
      } else if (formData.username.length < 3) {
        errors.username = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
      }

      if (!formData.password) {
        errors.password = 'كلمة السر مطلوبة';
      } else if (formData.password.length < 6) {
        errors.password = 'كلمة السر يجب أن تكون 6 أحرف على الأقل';
      }
    }

    if (!formData.email.trim()) {
      errors.email = 'البريد الإلكتروني مطلوب';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      errors.email = 'يرجى إدخال بريد إلكتروني صحيح';
    }

    if (!formData.display_name.trim()) {
      errors.display_name = 'الاسم الظاهر مطلوب';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    setSubmitting(true);

    try {
      if (modalMode === 'create') {
        await createUser(formData);
        alert('تم إنشاء المستخدم بنجاح!');
      } else {
        await updateUser(editingUser.id, formData);
        alert('تم تحديث المستخدم بنجاح!');
      }

      closeModal();
      await fetchUsers();
    } catch (err) {
      console.error('Error saving user:', err);
      alert(err.message || 'فشل حفظ المستخدم');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteConfirm) return;

    setDeleting(true);

    try {
      await deleteUser(deleteConfirm.id);
      alert('تم حذف المستخدم بنجاح!');
      setDeleteConfirm(null);
      await fetchUsers();
    } catch (err) {
      console.error('Error deleting user:', err);
      alert(err.message || 'فشل حذف المستخدم');
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 bg-slate-50">
      {/* Sidebar */}
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="users" />
      </div>

      {/* Mobile Navigation */}
      <div className="lg:hidden">
        <Navigation currentPage="users" />
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
            <h1 className="text-lg font-bold text-gray-900">إدارة المستخدمين</h1>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Desktop Header */}
          <div className="hidden lg:block mb-8">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">إدارة المستخدمين</h1>
                <p className="mt-2 text-gray-600">
                  إدارة مستخدمي ومسؤولي منصة AQOP
                </p>
              </div>
              <button
                onClick={openCreateModal}
                className="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition"
              >
                <Plus className="w-5 h-5 ml-2" />
                إضافة مستخدم جديد
              </button>
            </div>
          </div>

          {/* Mobile Add Button */}
          <div className="lg:hidden mb-6">
            <button
              onClick={openCreateModal}
              className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition font-bold"
            >
              <Plus size={20} />
              <span>مستخدم جديد</span>
            </button>
          </div>

          {/* Filters */}
          <div className="bg-white rounded-lg shadow p-4 lg:p-6 mb-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {/* Search */}
              <div className="md:col-span-2">
                <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                  بحث عن المستخدمين
                </label>
                <div className="relative">
                  <input
                    type="text"
                    id="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="بحث بالاسم، البريد أو المعرف..."
                    className="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-right"
                  />
                  <Search className="absolute left-3 top-2.5 text-gray-400 w-5 h-5" />
                </div>
              </div>

              {/* Role Filter */}
              <div>
                <label htmlFor="roleFilter" className="block text-sm font-medium text-gray-700 mb-1">
                  تصفية حسب الدور
                </label>
                <select
                  id="roleFilter"
                  value={roleFilter}
                  onChange={(e) => setRoleFilter(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                >
                  <option value="">جميع الأدوار</option>
                  {aqopRoles.map(role => (
                    <option key={role.value} value={role.value}>
                      {role.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          {/* Loading State */}
          {loading && (
            <div className="flex justify-center py-12">
              <LoadingSpinner size="lg" text="جاري تحميل المستخدمين..." />
            </div>
          )}

          {/* Error State */}
          {error && !loading && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
              <p className="text-red-800">{error}</p>
            </div>
          )}

          {/* Users Table */}
          {!loading && !error && (
            <div className="bg-white rounded-lg shadow overflow-hidden">
              <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 className="text-lg font-medium text-gray-900">
                  {filteredUsers.length} مستخدم
                </h3>
              </div>

              {filteredUsers.length === 0 ? (
                <div className="text-center py-12">
                  <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <User className="w-8 h-8 text-gray-400" />
                  </div>
                  <h3 className="mt-2 text-sm font-medium text-gray-900">لا يوجد مستخدمين</h3>
                  <p className="mt-1 text-sm text-gray-500">
                    {search || roleFilter ? 'حاول تعديل خيارات البحث' : 'ابدأ بإنشاء مستخدم جديد'}
                  </p>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200 text-right">
                    <thead className="bg-gray-50">
                      <tr>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          المستخدم
                        </th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          البريد الإلكتروني
                        </th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          الدور
                        </th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          تم الإنشاء
                        </th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          الدولة
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          الإجراءات
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                      {filteredUsers.map((user) => (
                        <tr key={user.id} className="hover:bg-gray-50">
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="flex items-center gap-4">
                              <div className="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span className="text-blue-600 font-medium text-sm">
                                  {user.display_name?.charAt(0) || user.username?.charAt(0) || 'U'}
                                </span>
                              </div>
                              <div>
                                <div className="text-sm font-medium text-gray-900">
                                  {user.display_name || user.username}
                                </div>
                                <div className="text-sm text-gray-500">
                                  @{user.username}
                                </div>
                              </div>
                            </div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="text-sm text-gray-900">{user.email}</div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                              {getRoleDisplayName(user.role)}
                            </span>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {user.registered ? new Date(user.registered).toLocaleDateString('en-GB') : 'N/A'}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {user.country_name ? (
                              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {user.country_name}
                              </span>
                            ) : (
                              <span className="text-gray-400 italic">عام (Global)</span>
                            )}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                            <button
                              onClick={() => openEditModal(user)}
                              className="text-blue-600 hover:text-blue-900 ml-4 p-2 hover:bg-blue-50 rounded-lg transition"
                              title="تعديل"
                            >
                              <Edit2 size={18} />
                            </button>
                            <button
                              onClick={() => setDeleteConfirm(user)}
                              disabled={user.id === currentUser?.id}
                              className="text-red-600 hover:text-red-900 disabled:opacity-50 disabled:cursor-not-allowed p-2 hover:bg-red-50 rounded-lg transition"
                              title={user.id === currentUser?.id ? "لا يمكنك حذف حسابك" : "حذف المستخدم"}
                            >
                              <Trash2 size={18} />
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}

          {/* Add/Edit User Modal */}
          {showModal && (
            <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
              <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                  <h2 className="text-xl font-bold text-gray-900">
                    {modalMode === 'create' ? 'إضافة مستخدم جديد' : 'تعديل مستخدم'}
                  </h2>
                  <button
                    onClick={closeModal}
                    className="p-2 hover:bg-gray-200 rounded-full text-gray-500 transition"
                  >
                    <X size={20} />
                  </button>
                </div>

                <div className="p-6 overflow-y-auto">
                  <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Username */}
                    {modalMode === 'create' && (
                      <div>
                        <label htmlFor="username" className="block text-sm font-medium text-gray-700 mb-1">
                          اسم المستخدم <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          id="username"
                          value={formData.username}
                          onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                          className={`w-full px-3 py-2 border ${formErrors.username ? 'border-red-300' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                          placeholder="username"
                        />
                        {formErrors.username && (
                          <p className="mt-1 text-sm text-red-600">{formErrors.username}</p>
                        )}
                      </div>
                    )}

                    {/* Email */}
                    <div>
                      <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                        البريد الإلكتروني <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="email"
                        id="email"
                        value={formData.email}
                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        className={`w-full px-3 py-2 border ${formErrors.email ? 'border-red-300' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                        placeholder="user@example.com"
                      />
                      {formErrors.email && (
                        <p className="mt-1 text-sm text-red-600">{formErrors.email}</p>
                      )}
                    </div>

                    {/* Password */}
                    <div>
                      <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                        كلمة المرور {modalMode === 'create' && <span className="text-red-500">*</span>}
                        {modalMode === 'edit' && <span className="text-gray-500 font-normal text-xs mr-2">(اتركها فارغة للاحتفاظ بالحالية)</span>}
                      </label>
                      <input
                        type="password"
                        id="password"
                        value={formData.password}
                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                        className={`w-full px-3 py-2 border ${formErrors.password ? 'border-red-300' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                        placeholder="••••••••"
                      />
                      {formErrors.password && (
                        <p className="mt-1 text-sm text-red-600">{formErrors.password}</p>
                      )}
                    </div>

                    {/* Display Name */}
                    <div>
                      <label htmlFor="display_name" className="block text-sm font-medium text-gray-700 mb-1">
                        الاسم الظاهر <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        id="display_name"
                        value={formData.display_name}
                        onChange={(e) => setFormData({ ...formData, display_name: e.target.value })}
                        className={`w-full px-3 py-2 border ${formErrors.display_name ? 'border-red-300' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                        placeholder="John Doe"
                      />
                      {formErrors.display_name && (
                        <p className="mt-1 text-sm text-red-600">{formErrors.display_name}</p>
                      )}
                    </div>

                    {/* Role */}
                    <div>
                      <label htmlFor="role" className="block text-sm font-medium text-gray-700 mb-1">
                        الدور (Role) <span className="text-red-500">*</span>
                      </label>
                      <select
                        id="role"
                        value={formData.role}
                        onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                      >
                        {aqopRoles.map(role => (
                          <option key={role.value} value={role.value}>
                            {role.label}
                          </option>
                        ))}
                      </select>
                    </div>

                    {/* Country Selection */}
                    <div>
                      <label htmlFor="country_id" className="block text-sm font-medium text-gray-700 mb-1">
                        الدولة المعينة
                      </label>
                      <select
                        id="country_id"
                        value={formData.country_id}
                        onChange={(e) => setFormData({ ...formData, country_id: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                        disabled={loadingCountries}
                      >
                        <option value="">لا يوجد (وصول عالمي)</option>
                        {countries.map((country) => (
                          <option key={country.id} value={country.id}>
                            {country.country_name_en}
                          </option>
                        ))}
                      </select>
                      <p className="mt-1 text-xs text-gray-500">
                        تعيين دولة يقيد الصلاحيات لمديري الدول فقط.
                      </p>
                    </div>

                    {/* Buttons */}
                    <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                      <button
                        type="button"
                        onClick={closeModal}
                        className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                        disabled={submitting}
                      >
                        إلغاء
                      </button>
                      <button
                        type="submit"
                        disabled={submitting}
                        className="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50"
                      >
                        {submitting ? 'جاري الحفظ...' : modalMode === 'create' ? 'إنشاء مستخدم' : 'تحديث المستخدم'}
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          )}

          {/* Delete Confirmation Modal */}
          {deleteConfirm && (
            <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
              <div className="bg-white rounded-xl shadow-xl p-8 max-w-md w-full">
                <div className="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                  <Trash2 className="w-6 h-6 text-red-600" />
                </div>

                <h3 className="text-lg font-bold text-gray-900 text-center mb-2">
                  حذف المستخدم
                </h3>
                <p className="text-sm text-gray-500 text-center mb-6">
                  هل أنت متأكد أنك تريد حذف <strong>{deleteConfirm.display_name || deleteConfirm.username}</strong>؟
                  هذا الإجراء لا يمكن التراجع عنه.
                </p>

                <div className="flex justify-end gap-3">
                  <button
                    onClick={() => setDeleteConfirm(null)}
                    className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                    disabled={deleting}
                  >
                    إلغاء
                  </button>
                  <button
                    onClick={handleDelete}
                    disabled={deleting}
                    className="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition disabled:opacity-50"
                  >
                    {deleting ? 'جاري الحذف...' : 'حذف نهائي'}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </main>

      {/* Bottom Navigation for Mobile */}
      <BottomNav />
    </div>
  );
}

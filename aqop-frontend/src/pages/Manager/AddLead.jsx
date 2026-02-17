/**
 * Add Lead Page (Internal/Admin)
 * 
 * Allows authenticated users to manually add a new lead.
 * Includes all fields: name, email, phone, whatsapp, country, source, campaign, status, priority, notes.
 * Auto-syncs to Airtable via backend.
 */

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { createLead, getCountries, getLearningPaths } from '../../api/leads';
import { getAgents } from '../../api/users';
import { useAuth } from '../../auth/AuthContext';
import Navigation from '../../components/Navigation';
import LoadingSpinner from '../../components/LoadingSpinner';

export default function AddLead() {
  const navigate = useNavigate();
  const { user } = useAuth();

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    whatsapp: '',
    country_id: '',
    source_id: '',
    campaign_id: '',
    status_code: 'pending',
    assigned_to: '',
    priority: 'medium',
    notes: '',
    learning_path_id: '',
  });

  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [serverError, setServerError] = useState('');

  // Dropdown data
  const [countries, setCountries] = useState([]);
  const [agents, setAgents] = useState([]);
  const [learningPaths, setLearningPaths] = useState([]);
  const [loadingData, setLoadingData] = useState(true);

  // Can this user assign leads?
  const canAssign = ['administrator', 'operation_admin', 'operation_manager', 'aq_country_manager'].includes(user?.role);

  useEffect(() => {
    loadDropdownData();
  }, []);

  const loadDropdownData = async () => {
    setLoadingData(true);
    try {
      // Load countries
      try {
        const countriesRes = await getCountries();
        if (countriesRes?.data) {
          setCountries(countriesRes.data);
        }
      } catch (e) {
        console.error('Error loading countries:', e);
      }

      // Load learning paths
      try {
        const lpRes = await getLearningPaths();
        if (lpRes?.data) {
          setLearningPaths(lpRes.data);
        }
      } catch (e) {
        console.error('Error loading learning paths:', e);
      }

      // Load agents for assignment
      if (canAssign) {
        try {
          const agentsRes = await getAgents();
          if (agentsRes) {
            setAgents(Array.isArray(agentsRes) ? agentsRes : agentsRes.data || []);
          }
        } catch (e) {
          console.error('Error loading agents:', e);
        }
      }
    } finally {
      setLoadingData(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));

    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'الاسم مطلوب';
    }

    if (!formData.phone.trim()) {
      newErrors.phone = 'رقم الهاتف مطلوب';
    } else if (!/^[\d\s\+\-\(\)]+$/.test(formData.phone)) {
      newErrors.phone = 'رقم هاتف غير صالح';
    }

    if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = 'بريد إلكتروني غير صالح';
    }

    if (formData.whatsapp && !/^[\d\s\+\-\(\)]+$/.test(formData.whatsapp)) {
      newErrors.whatsapp = 'رقم واتساب غير صالح';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setServerError('');

    if (!validateForm()) return;

    setSubmitting(true);

    try {
      const leadData = {
        name: formData.name.trim(),
        phone: formData.phone.trim(),
      };

      // Optional fields
      if (formData.email.trim()) leadData.email = formData.email.trim();
      if (formData.whatsapp.trim()) leadData.whatsapp = formData.whatsapp.trim();
      if (formData.country_id) leadData.country_id = parseInt(formData.country_id);
      if (formData.source_id) leadData.source_id = parseInt(formData.source_id);
      if (formData.campaign_id) leadData.campaign_id = parseInt(formData.campaign_id);
      if (formData.status_code) leadData.status_code = formData.status_code;
      if (formData.assigned_to) leadData.assigned_to = parseInt(formData.assigned_to);
      if (formData.priority) leadData.priority = formData.priority;
      if (formData.notes.trim()) leadData.note = formData.notes.trim();

      const response = await createLead(leadData);

      if (response?.success || response?.data) {
        const newLeadId = response?.data?.id || response?.data?.lead_id;
        if (newLeadId) {
          navigate(`/leads/${newLeadId}`);
        } else {
          navigate('/leads');
        }
      } else {
        setServerError(response?.message || 'فشل في إضافة الليد. حاول مرة أخرى.');
      }
    } catch (err) {
      console.error('Error creating lead:', err);
      setServerError(err?.response?.data?.message || err.message || 'فشل في إضافة الليد. حاول مرة أخرى.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loadingData) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <LoadingSpinner size="lg" text="جاري التحميل..." />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Desktop Sidebar */}
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="add-lead" />
      </div>

      {/* Mobile Navigation */}
      <div className="lg:hidden">
        <Navigation currentPage="add-lead" />
      </div>

      {/* Main Content */}
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 lg:py-8">

          {/* Header */}
          <div className="flex items-center justify-between mb-6">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">إضافة ليد جديد</h1>
              <p className="text-sm text-gray-500 mt-1">أدخل بيانات العميل المحتمل</p>
            </div>
            <button
              onClick={() => navigate(-1)}
              className="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
              رجوع
            </button>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-6">

            {/* Server Error */}
            {serverError && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <p className="text-sm text-red-800">{serverError}</p>
              </div>
            )}

            {/* Basic Info Card */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                المعلومات الأساسية
              </h2>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Name */}
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    الاسم الكامل <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    placeholder="اسم العميل..."
                    dir="auto"
                    className={`w-full px-4 py-3 border ${errors.name ? 'border-red-300 bg-red-50' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                  />
                  {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                </div>

                {/* Email */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    البريد الإلكتروني
                  </label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    placeholder="email@example.com"
                    className={`w-full px-4 py-3 border ${errors.email ? 'border-red-300 bg-red-50' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                  />
                  {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
                </div>

                {/* Phone */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    رقم الهاتف <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    placeholder="+966 50 123 4567"
                    className={`w-full px-4 py-3 border ${errors.phone ? 'border-red-300 bg-red-50' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                  />
                  {errors.phone && <p className="mt-1 text-xs text-red-600">{errors.phone}</p>}
                </div>

                {/* WhatsApp */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    رقم الواتساب
                  </label>
                  <input
                    type="tel"
                    name="whatsapp"
                    value={formData.whatsapp}
                    onChange={handleChange}
                    placeholder="إذا كان مختلفاً عن الهاتف"
                    className={`w-full px-4 py-3 border ${errors.whatsapp ? 'border-red-300 bg-red-50' : 'border-gray-300'} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500`}
                  />
                  {errors.whatsapp && <p className="mt-1 text-xs text-red-600">{errors.whatsapp}</p>}
                </div>

                {/* Country */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">الدولة</label>
                  <select
                    name="country_id"
                    value={formData.country_id}
                    onChange={handleChange}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">-- اختر الدولة --</option>
                    {countries.map(c => (
                      <option key={c.id} value={c.id}>{c.name_en || c.country_name_en || c.name}</option>
                    ))}
                  </select>
                </div>
              </div>
            </div>

            {/* Classification Card */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                التصنيف
              </h2>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Source */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">المصدر</label>
                  <select
                    name="source_id"
                    value={formData.source_id}
                    onChange={handleChange}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">-- اختر المصدر --</option>
                    <option value="1">Facebook Ads</option>
                    <option value="2">Google Ads</option>
                    <option value="3">Instagram Ads</option>
                    <option value="4">Website Form</option>
                    <option value="5">Referral</option>
                    <option value="6">Direct Contact</option>
                  </select>
                </div>

                {/* Status */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                  <select
                    name="status_code"
                    value={formData.status_code}
                    onChange={handleChange}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="pending">Pending - معلق</option>
                    <option value="contacted">Contacted - تم الاتصال</option>
                    <option value="qualified">Qualified - مؤهل</option>
                    <option value="converted">Converted - محول</option>
                    <option value="lost">Lost - خاسر</option>
                  </select>
                </div>

                {/* Priority */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">الأولوية</label>
                  <div className="flex gap-2">
                    {[
                      { value: 'low', label: 'منخفضة', color: 'gray' },
                      { value: 'medium', label: 'متوسطة', color: 'blue' },
                      { value: 'high', label: 'عالية', color: 'orange' },
                      { value: 'urgent', label: 'عاجلة', color: 'red' },
                    ].map(p => (
                      <button
                        key={p.value}
                        type="button"
                        onClick={() => setFormData(prev => ({ ...prev, priority: p.value }))}
                        className={`flex-1 py-2 rounded-lg text-xs font-medium transition-all ${
                          formData.priority === p.value
                            ? p.color === 'red'
                              ? 'bg-red-600 text-white'
                              : p.color === 'orange'
                              ? 'bg-orange-500 text-white'
                              : p.color === 'blue'
                              ? 'bg-blue-600 text-white'
                              : 'bg-gray-600 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        }`}
                      >
                        {p.label}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Assigned To */}
                {canAssign && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">تعيين إلى</label>
                    <select
                      name="assigned_to"
                      value={formData.assigned_to}
                      onChange={handleChange}
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">-- غير معين --</option>
                      {agents.map(agent => (
                        <option key={agent.id || agent.ID} value={agent.id || agent.ID}>
                          {agent.display_name || agent.name}
                        </option>
                      ))}
                    </select>
                  </div>
                )}
              </div>
            </div>

            {/* Notes Card */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                ملاحظات
              </h2>
              <textarea
                name="notes"
                value={formData.notes}
                onChange={handleChange}
                placeholder="أي ملاحظات إضافية عن هذا العميل المحتمل..."
                rows={4}
                dir="auto"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            {/* Learning Path */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                المسار التعليمي
              </h2>
              <select
                name="learning_path_id"
                value={formData.learning_path_id}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                dir="auto"
              >
                <option value="">-- اختر المسار التعليمي --</option>
                {learningPaths.map(path => (
                  <option key={path.id} value={path.id}>
                    {path.name_ar} / {path.name_en}
                  </option>
                ))}
              </select>
            </div>

            {/* Submit Buttons */}
            <div className="flex gap-3">
              <button
                type="submit"
                disabled={submitting}
                className="flex-1 flex justify-center items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-[0.98] min-h-[48px] touch-manipulation"
              >
                {submitting ? (
                  <>
                    <svg className="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    جاري الحفظ...
                  </>
                ) : (
                  <>
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    حفظ الليد
                  </>
                )}
              </button>
              <button
                type="button"
                onClick={() => navigate(-1)}
                disabled={submitting}
                className="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all min-h-[48px]"
              >
                إلغاء
              </button>
            </div>

          </form>
        </div>
      </main>
    </div>
  );
}

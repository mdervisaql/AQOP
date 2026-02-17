/**
 * FAQ Management Page
 * 
 * Admin/Manager/Country Manager can add, edit, delete FAQ items.
 * FAQs are filterable by country and category.
 */

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getFaqs, getFaqCategories, createFaq, updateFaq, deleteFaq } from '../../api/faq';
import { getCountries } from '../../api/leads';
import { useAuth } from '../../auth/AuthContext';
import Navigation from '../../components/Navigation';
import LoadingSpinner from '../../components/LoadingSpinner';

export default function FAQSettings() {
  const navigate = useNavigate();
  const { user } = useAuth();

  const [faqs, setFaqs] = useState([]);
  const [countries, setCountries] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  // Filters
  const [filterCountry, setFilterCountry] = useState('');
  const [filterCategory, setFilterCategory] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  // Form
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [formData, setFormData] = useState({
    question: '',
    answer: '',
    country_id: '',
    category: '',
    display_order: 0,
  });
  const [newCategory, setNewCategory] = useState('');

  const isAdmin = ['administrator', 'operation_admin', 'operation_manager'].includes(user?.role);
  const isCountryManager = user?.role === 'aq_country_manager';

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    loadFaqs();
  }, [filterCountry, filterCategory]);

  const loadData = async () => {
    setLoading(true);
    try {
      const [countriesRes, categoriesRes] = await Promise.all([
        getCountries(),
        getFaqCategories(),
      ]);
      if (countriesRes?.data) setCountries(countriesRes.data);
      if (categoriesRes?.data) setCategories(categoriesRes.data);
      await loadFaqs();
    } catch (err) {
      console.error('Error loading data:', err);
      setError('فشل في تحميل البيانات');
    } finally {
      setLoading(false);
    }
  };

  const loadFaqs = async () => {
    try {
      const params = {};
      if (filterCountry) params.country_id = filterCountry;
      if (filterCategory) params.category = filterCategory;
      const res = await getFaqs(params);
      if (res?.data) setFaqs(res.data);
    } catch (err) {
      console.error('Error loading FAQs:', err);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!formData.question.trim() || !formData.answer.trim()) {
      setError('يرجى إدخال السؤال والإجابة');
      return;
    }

    const finalCategory = newCategory.trim() || formData.category;

    setSaving(true);
    try {
      const payload = {
        ...formData,
        category: finalCategory || null,
        country_id: formData.country_id ? parseInt(formData.country_id) : null,
      };

      if (editingId) {
        await updateFaq(editingId, payload);
        setSuccessMessage('تم تحديث السؤال بنجاح');
      } else {
        await createFaq(payload);
        setSuccessMessage('تم إضافة السؤال بنجاح');
      }

      resetForm();
      await loadFaqs();
      // Refresh categories
      const catRes = await getFaqCategories();
      if (catRes?.data) setCategories(catRes.data);

      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Error saving FAQ:', err);
      setError('فشل في حفظ السؤال');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (faq) => {
    setEditingId(faq.id);
    setFormData({
      question: faq.question,
      answer: faq.answer,
      country_id: faq.country_id || '',
      category: faq.category || '',
      display_order: faq.display_order || 0,
    });
    setNewCategory('');
    setShowForm(true);
    setError('');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleDelete = async (faqId) => {
    if (!confirm('هل أنت متأكد من حذف هذا السؤال؟')) return;
    try {
      await deleteFaq(faqId);
      setSuccessMessage('تم حذف السؤال');
      await loadFaqs();
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Error deleting FAQ:', err);
      setError('فشل في حذف السؤال');
    }
  };

  const resetForm = () => {
    setFormData({ question: '', answer: '', country_id: '', category: '', display_order: 0 });
    setNewCategory('');
    setEditingId(null);
    setShowForm(false);
    setError('');
  };

  const getCountryName = (faq) => {
    if (!faq.country_id) return 'عام (جميع الدول)';
    return faq.country_name_ar || faq.country_name_en || `Country #${faq.country_id}`;
  };

  // Filter FAQs by search term (client-side)
  const filteredFaqs = searchTerm
    ? faqs.filter(f =>
      f.question.toLowerCase().includes(searchTerm.toLowerCase()) ||
      f.answer.toLowerCase().includes(searchTerm.toLowerCase())
    )
    : faqs;

  // Group FAQs by country
  const groupedFaqs = {};
  filteredFaqs.forEach(faq => {
    const key = faq.country_id ? `${faq.country_name_ar || faq.country_name_en}` : 'عام (جميع الدول)';
    if (!groupedFaqs[key]) groupedFaqs[key] = [];
    groupedFaqs[key].push(faq);
  });

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
          <Navigation currentPage="faq" />
        </div>
        <main className="lg:ml-64 pt-14 lg:pt-0">
          <div className="flex items-center justify-center min-h-screen">
            <LoadingSpinner size="lg" text="جاري التحميل..." />
          </div>
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="faq" />
      </div>
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-0">
      <div className="max-w-5xl mx-auto px-4 py-6 lg:py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center gap-3">
            <button onClick={() => navigate(-1)} className="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div>
              <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg className="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                الأسئلة المتوقعة والإجابات المعتمدة
              </h1>
              <p className="text-sm text-gray-500 mt-1">إدارة الأسئلة والأجوبة المعتمدة للتواصل مع العملاء - حسب الدولة</p>
            </div>
          </div>
          {(isAdmin || isCountryManager) && !showForm && (
            <button
              onClick={() => { setShowForm(true); setEditingId(null); resetForm(); setShowForm(true); }}
              className="flex items-center gap-2 px-4 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-medium text-sm"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              إضافة سؤال جديد
            </button>
          )}
        </div>

        {/* Messages */}
        {successMessage && (
          <div className="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" /></svg>
            {successMessage}
          </div>
        )}
        {error && (
          <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" /></svg>
            {error}
          </div>
        )}

        {/* Add/Edit Form */}
        {showForm && (
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              {editingId ? 'تعديل السؤال' : 'إضافة سؤال جديد'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              {/* Country + Category row */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {/* Country */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">الدولة</label>
                  {isCountryManager && !isAdmin ? (
                    <div className="px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600">
                      دولتك المحددة (تلقائي)
                    </div>
                  ) : (
                    <select
                      value={formData.country_id}
                      onChange={(e) => setFormData({ ...formData, country_id: e.target.value })}
                      className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                    >
                      <option value="">عام (جميع الدول)</option>
                      {countries.map(c => (
                        <option key={c.id} value={c.id}>{c.country_name_ar} / {c.country_name_en}</option>
                      ))}
                    </select>
                  )}
                </div>

                {/* Category */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">التصنيف</label>
                  <select
                    value={formData.category}
                    onChange={(e) => { setFormData({ ...formData, category: e.target.value }); if (e.target.value) setNewCategory(''); }}
                    className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                  >
                    <option value="">بدون تصنيف</option>
                    {categories.map(cat => (
                      <option key={cat} value={cat}>{cat}</option>
                    ))}
                  </select>
                </div>

                {/* New category */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">أو أضف تصنيف جديد</label>
                  <input
                    type="text"
                    value={newCategory}
                    onChange={(e) => { setNewCategory(e.target.value); if (e.target.value) setFormData({ ...formData, category: '' }); }}
                    placeholder="اسم التصنيف الجديد..."
                    className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                    dir="auto"
                  />
                </div>
              </div>

              {/* Question */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  السؤال <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={formData.question}
                  onChange={(e) => setFormData({ ...formData, question: e.target.value })}
                  placeholder="اكتب السؤال المتوقع من العميل..."
                  rows={2}
                  className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"
                  dir="auto"
                  required
                />
              </div>

              {/* Answer */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الإجابة المعتمدة <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={formData.answer}
                  onChange={(e) => setFormData({ ...formData, answer: e.target.value })}
                  placeholder="اكتب الإجابة المعتمدة التي يجب أن يستخدمها الموظف..."
                  rows={4}
                  className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"
                  dir="auto"
                  required
                />
              </div>

              {/* Order */}
              <div className="w-32">
                <label className="block text-sm font-medium text-gray-700 mb-1">ترتيب العرض</label>
                <input
                  type="number"
                  value={formData.display_order}
                  onChange={(e) => setFormData({ ...formData, display_order: parseInt(e.target.value) || 0 })}
                  className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"
                  min="0"
                />
              </div>

              <div className="flex gap-3 pt-2">
                <button type="submit" disabled={saving} className="flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 transition-colors font-medium text-sm">
                  {saving ? (
                    <><svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg> جاري الحفظ...</>
                  ) : (
                    <><svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg> {editingId ? 'تحديث' : 'إضافة'}</>
                  )}
                </button>
                <button type="button" onClick={resetForm} className="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                  إلغاء
                </button>
              </div>
            </form>
          </div>
        )}

        {/* Filters */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
          <div className="flex flex-wrap gap-3 items-end">
            <div className="flex-1 min-w-[200px]">
              <label className="block text-xs font-medium text-gray-500 mb-1">بحث</label>
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="ابحث في الأسئلة والأجوبة..."
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                dir="auto"
              />
            </div>
            {isAdmin && (
              <div className="min-w-[180px]">
                <label className="block text-xs font-medium text-gray-500 mb-1">الدولة</label>
                <select
                  value={filterCountry}
                  onChange={(e) => setFilterCountry(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                >
                  <option value="">جميع الدول</option>
                  {countries.map(c => (
                    <option key={c.id} value={c.id}>{c.country_name_ar}</option>
                  ))}
                </select>
              </div>
            )}
            <div className="min-w-[160px]">
              <label className="block text-xs font-medium text-gray-500 mb-1">التصنيف</label>
              <select
                value={filterCategory}
                onChange={(e) => setFilterCategory(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
              >
                <option value="">جميع التصنيفات</option>
                {categories.map(cat => (
                  <option key={cat} value={cat}>{cat}</option>
                ))}
              </select>
            </div>
          </div>
        </div>

        {/* FAQ List */}
        {filteredFaqs.length === 0 ? (
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p className="text-gray-500 text-lg mb-2">لا توجد أسئلة وأجوبة حتى الآن</p>
            <p className="text-gray-400 text-sm">اضغط على "إضافة سؤال جديد" لبدء الإضافة</p>
          </div>
        ) : (
          Object.entries(groupedFaqs).map(([countryName, countryFaqs]) => (
            <div key={countryName} className="mb-6">
              <div className="flex items-center gap-2 mb-3">
                <span className="bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">
                  {countryName}
                </span>
                <span className="text-xs text-gray-400">{countryFaqs.length} سؤال</span>
              </div>
              <div className="space-y-3">
                {countryFaqs.map((faq) => (
                  <div key={faq.id} className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="p-5">
                      <div className="flex items-start justify-between gap-4">
                        <div className="flex-1 min-w-0">
                          {faq.category && (
                            <span className="inline-block bg-gray-100 text-gray-600 text-[11px] font-medium px-2 py-0.5 rounded mb-2">
                              {faq.category}
                            </span>
                          )}
                          <div className="flex items-start gap-2 mb-3">
                            <span className="text-amber-600 font-bold text-lg mt-0.5 flex-shrink-0">س:</span>
                            <p className="text-gray-900 font-medium" dir="auto">{faq.question}</p>
                          </div>
                          <div className="flex items-start gap-2 bg-green-50 rounded-lg p-3 border border-green-100">
                            <span className="text-green-600 font-bold text-lg mt-0.5 flex-shrink-0">ج:</span>
                            <p className="text-gray-700 whitespace-pre-wrap" dir="auto">{faq.answer}</p>
                          </div>
                        </div>
                        {(isAdmin || isCountryManager) && (
                          <div className="flex items-center gap-1 flex-shrink-0">
                            <button onClick={() => handleEdit(faq)} className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="تعديل">
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button onClick={() => handleDelete(faq.id)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="حذف">
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))
        )}
      </div>
      </main>
    </div>
  );
}

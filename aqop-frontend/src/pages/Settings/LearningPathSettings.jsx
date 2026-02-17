/**
 * Learning Path Settings - Admin Page
 * 
 * Allows admins to manage learning paths that can be assigned to leads.
 * CRUD operations: Create, Read, Update, Delete (soft delete/deactivate)
 */

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getLearningPaths, createLearningPath, updateLearningPath, deleteLearningPath } from '../../api/leads';
import { useAuth } from '../../auth/AuthContext';
import Navigation from '../../components/Navigation';
import LoadingSpinner from '../../components/LoadingSpinner';

export default function LearningPathSettings() {
  const navigate = useNavigate();
  const { user } = useAuth();

  const [paths, setPaths] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [showAddForm, setShowAddForm] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  const [formData, setFormData] = useState({
    name_ar: '',
    name_en: '',
    description: '',
    display_order: 0,
  });

  useEffect(() => {
    loadPaths();
  }, []);

  const loadPaths = async () => {
    setLoading(true);
    try {
      const response = await getLearningPaths();
      if (response?.data) {
        setPaths(response.data);
      }
    } catch (err) {
      console.error('Error loading learning paths:', err);
      setError('فشل في تحميل المسارات التعليمية');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!formData.name_ar.trim() || !formData.name_en.trim()) {
      setError('يرجى إدخال الاسم بالعربية والإنجليزية');
      return;
    }

    setSaving(true);
    try {
      if (editingId) {
        await updateLearningPath(editingId, formData);
        setSuccessMessage('تم تحديث المسار التعليمي بنجاح');
      } else {
        await createLearningPath(formData);
        setSuccessMessage('تم إضافة المسار التعليمي بنجاح');
      }

      setFormData({ name_ar: '', name_en: '', description: '', display_order: 0 });
      setEditingId(null);
      setShowAddForm(false);
      await loadPaths();

      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Error saving learning path:', err);
      setError('فشل في حفظ المسار التعليمي');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (path) => {
    setEditingId(path.id);
    setFormData({
      name_ar: path.name_ar,
      name_en: path.name_en,
      description: path.description || '',
      display_order: path.display_order || 0,
    });
    setShowAddForm(true);
    setError('');
  };

  const handleDelete = async (pathId) => {
    if (!confirm('هل أنت متأكد من حذف هذا المسار التعليمي؟')) return;

    try {
      await deleteLearningPath(pathId);
      setSuccessMessage('تم حذف المسار التعليمي');
      await loadPaths();
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Error deleting learning path:', err);
      setError('فشل في حذف المسار التعليمي');
    }
  };

  const handleCancel = () => {
    setFormData({ name_ar: '', name_en: '', description: '', display_order: 0 });
    setEditingId(null);
    setShowAddForm(false);
    setError('');
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
          <Navigation currentPage="learning-paths" />
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
        <Navigation currentPage="learning-paths" />
      </div>
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-0">
      <div className="max-w-4xl mx-auto px-4 py-6 lg:py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div className="flex items-center gap-3">
            <button
              onClick={() => navigate(-1)}
              className="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div>
              <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg className="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                إدارة المسارات التعليمية
              </h1>
              <p className="text-sm text-gray-500 mt-1">إضافة وتعديل المسارات التعليمية المتاحة للعملاء المحتملين</p>
            </div>
          </div>
          {!showAddForm && (
            <button
              onClick={() => { setShowAddForm(true); setEditingId(null); setFormData({ name_ar: '', name_en: '', description: '', display_order: 0 }); }}
              className="flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              إضافة مسار جديد
            </button>
          )}
        </div>

        {/* Success Message */}
        {successMessage && (
          <div className="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
            </svg>
            {successMessage}
          </div>
        )}

        {/* Error Message */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            {error}
          </div>
        )}

        {/* Add/Edit Form */}
        {showAddForm && (
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              {editingId ? 'تعديل المسار التعليمي' : 'إضافة مسار تعليمي جديد'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    الاسم بالعربية <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.name_ar}
                    onChange={(e) => setFormData({ ...formData, name_ar: e.target.value })}
                    placeholder="مثال: الطلاقة (القرآن الكريم)"
                    className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    dir="rtl"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    الاسم بالإنجليزية <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.name_en}
                    onChange={(e) => setFormData({ ...formData, name_en: e.target.value })}
                    placeholder="Example: Fluency (Quran)"
                    className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    dir="ltr"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">الوصف (اختياري)</label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  placeholder="وصف مختصر للمسار التعليمي..."
                  rows={2}
                  className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                  dir="auto"
                />
              </div>

              <div className="w-32">
                <label className="block text-sm font-medium text-gray-700 mb-1">ترتيب العرض</label>
                <input
                  type="number"
                  value={formData.display_order}
                  onChange={(e) => setFormData({ ...formData, display_order: parseInt(e.target.value) || 0 })}
                  className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                  min="0"
                />
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="submit"
                  disabled={saving}
                  className="flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors font-medium text-sm"
                >
                  {saving ? (
                    <>
                      <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      جاري الحفظ...
                    </>
                  ) : (
                    <>
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                      {editingId ? 'تحديث' : 'إضافة'}
                    </>
                  )}
                </button>
                <button
                  type="button"
                  onClick={handleCancel}
                  className="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm"
                >
                  إلغاء
                </button>
              </div>
            </form>
          </div>
        )}

        {/* Paths List */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 className="font-semibold text-gray-900 flex items-center gap-2">
              المسارات التعليمية الحالية
              <span className="bg-purple-100 text-purple-700 text-xs font-medium px-2 py-0.5 rounded-full">{paths.length}</span>
            </h2>
          </div>

          {paths.length === 0 ? (
            <div className="p-12 text-center">
              <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              <p className="text-gray-500 text-lg mb-2">لا توجد مسارات تعليمية حتى الآن</p>
              <p className="text-gray-400 text-sm">اضغط على "إضافة مسار جديد" لبدء الإضافة</p>
            </div>
          ) : (
            <div className="divide-y divide-gray-100">
              {paths.map((path, index) => (
                <div
                  key={path.id}
                  className="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-center gap-4">
                    <div className="flex-shrink-0 w-10 h-10 bg-purple-100 text-purple-700 rounded-lg flex items-center justify-center font-bold text-sm">
                      {path.display_order || index + 1}
                    </div>
                    <div>
                      <div className="font-medium text-gray-900" dir="rtl">{path.name_ar}</div>
                      <div className="text-sm text-gray-500" dir="ltr">{path.name_en}</div>
                      {path.description && (
                        <div className="text-xs text-gray-400 mt-0.5" dir="auto">{path.description}</div>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => handleEdit(path)}
                      className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      title="تعديل"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      onClick={() => handleDelete(path.id)}
                      className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      title="حذف"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
      </main>
    </div>
  );
}

import React, { useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import facebookApi from '../../api/facebook';

import Navigation from '../../components/Navigation';
import BottomNav from '../../components/BottomNav';
import {
    Facebook,
    CheckCircle,
    AlertTriangle,
    RefreshCw,
    Unlink,
    ArrowRight,
    Loader2
} from 'lucide-react';

const FacebookLeadsSettings = () => {
    const [loading, setLoading] = useState(true);
    const [connection, setConnection] = useState(null);
    const [pages, setPages] = useState([]);
    const [selectedPage, setSelectedPage] = useState(null);
    const [forms, setForms] = useState([]);
    const [selectedForm, setSelectedForm] = useState(null);
    const [formFields, setFormFields] = useState([]);
    const [mappings, setMappings] = useState({});
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [successMsg, setSuccessMsg] = useState(null);

    const location = useLocation();
    const navigate = useNavigate();

    // Redirect URI for OAuth
    const redirectUri = window.location.origin + '/settings/facebook-leads';

    useEffect(() => {
        fetchConnection();
    }, []);

    useEffect(() => {
        // Handle OAuth Callback
        const query = new URLSearchParams(location.search);
        const code = query.get('code');
        if (code) {
            handleOAuthCallback(code);
        }
    }, [location]);

    const fetchConnection = async () => {
        try {
            setLoading(true);
            const res = await facebookApi.getConnection();
            setConnection(res.data);
            if (res.data.connected) {
                fetchPages();
            }
        } catch (err) {
            console.error(err);
            setError('فشل في جلب حالة الاتصال.');
        } finally {
            setLoading(false);
        }
    };

    const handleOAuthCallback = async (code) => {
        try {
            setLoading(true);
            await facebookApi.handleOAuthCallback(code, redirectUri);
            setSuccessMsg('تم الاتصال بفيسبوك بنجاح!');
            navigate('/settings/facebook-leads', { replace: true });
            fetchConnection();
        } catch (err) {
            console.error(err);
            setError('فشل الاتصال بفيسبوك. يرجى المحاولة مرة أخرى.');
        } finally {
            setLoading(false);
        }
    };

    const handleConnect = async () => {
        try {
            const res = await facebookApi.getOAuthUrl(redirectUri);
            window.location.href = res.data.url;
        } catch (err) {
            console.error(err);
            setError('فشل في بدء الاتصال.');
        }
    };

    const handleDisconnect = async () => {
        if (!window.confirm('هل أنت متأكد أنك تريد قطع الاتصال؟ ستتوقف مزامنة الليدات.')) return;
        try {
            await facebookApi.disconnect();
            setConnection({ connected: false });
            setPages([]);
            setForms([]);
            setSelectedPage(null);
            setSelectedForm(null);
            setSuccessMsg('تم قطع الاتصال بنجاح.');
        } catch (err) {
            console.error(err);
            setError('فشل في قطع الاتصال.');
        }
    };

    const fetchPages = async () => {
        try {
            const res = await facebookApi.getPages();
            setPages(res.data);
        } catch (err) {
            console.error(err);
            setError('فشل في جلب الصفحات.');
        }
    };

    const handlePageSelect = async (e) => {
        const pageId = e.target.value;
        const page = pages.find(p => p.id === pageId);
        setSelectedPage(page);
        setSelectedForm(null);
        setForms([]);

        if (page) {
            try {
                const res = await facebookApi.getForms(page.id, page.access_token);
                setForms(res.data);
            } catch (err) {
                console.error(err);
                setError('فشل في جلب النماذج.');
            }
        }
    };

    const handleFormSelect = async (e) => {
        const formId = e.target.value;
        const form = forms.find(f => f.id === formId);
        setSelectedForm(form);

        if (form) {
            try {
                // Fetch fields for mapping
                const res = await facebookApi.getFormFields(form.id, selectedPage.access_token);
                setFormFields(res.data);
                // Reset mappings or load existing if we had an endpoint for it (currently getForms returns status but not full mapping)
                // Ideally we should fetch existing mapping for this form if it exists.
                // For now, we start fresh or need to implement getMapping endpoint.
                setMappings({});
            } catch (err) {
                console.error(err);
                setError('فشل في جلب حقول النموذج.');
            }
        }
    };

    const handleMappingChange = (fbField, type, value) => {
        setMappings(prev => ({
            ...prev,
            [fbField]: { type, value }
        }));
    };

    const saveMapping = async () => {
        if (!selectedForm || !selectedPage) return;

        try {
            setSaving(true);
            const mappingData = Object.entries(mappings).map(([fbField, map]) => ({
                fb_field: fbField,
                fb_label: formFields.find(f => f.name === fbField)?.label || fbField,
                [map.type === 'core' ? 'wp_field' : 'question_id']: map.value
            }));

            await facebookApi.saveMapping(selectedForm.id, {
                form_name: selectedForm.name,
                page_id: selectedPage.id,
                campaign_group_id: null, // Add UI for this later
                mappings: mappingData
            });

            setSuccessMsg('تم حفظ التعيين بنجاح!');
            // Refresh forms to update status
            const res = await facebookApi.getForms(selectedPage.id, selectedPage.access_token);
            setForms(res.data);
        } catch (err) {
            console.error(err);
            setError('فشل في حفظ التعيين.');
        } finally {
            setSaving(false);
        }
    };

    // Hardcoded WP fields for now - should fetch from API
    const wpFields = [
        { value: 'name', label: 'الاسم (Name)' },
        { value: 'email', label: 'البريد الإلكتروني (Email)' },
        { value: 'phone', label: 'الهاتف (Phone)' },
        { value: 'notes', label: 'ملاحظات (Notes)' },
        { value: 'city', label: 'المدينة (City)' },
        { value: 'project', label: 'المشروع (Project)' },
    ];

    if (loading && !connection) {
        return (
            <div className="flex h-screen bg-gray-50 items-center justify-center">
                <Loader2 className="animate-spin text-4xl text-blue-600" />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 bg-slate-50">
            {/* Sidebar */}
            <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
                <Navigation currentPage="facebook-leads" />
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden">
                <Navigation currentPage="facebook-leads" />
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
                        <h1 className="text-lg font-bold text-slate-900">إعدادات فيسبوك</h1>
                    </div>
                </div>

                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="space-y-6">

                        {/* Header & Connection Status */}
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                                <div className="flex items-center gap-3">
                                    <Facebook className="text-4xl text-blue-600" />
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-800">إعلانات ليد فيسبوك</h2>
                                        <p className="text-sm text-gray-500">مزامنة الليدات من نماذج فيسبوك</p>
                                    </div>
                                </div>
                                <div>
                                    {connection?.connected ? (
                                        <button
                                            onClick={handleDisconnect}
                                            className="flex items-center gap-2 px-4 py-2 border border-red-200 text-red-600 rounded hover:bg-red-50 transition w-full sm:w-auto justify-center"
                                        >
                                            <Unlink size={18} /> قطع الاتصال
                                        </button>
                                    ) : (
                                        <button
                                            onClick={handleConnect}
                                            className="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition font-medium w-full sm:w-auto justify-center"
                                        >
                                            <Facebook size={18} /> الاتصال بفيسبوك
                                        </button>
                                    )}
                                </div>
                            </div>

                            {error && (
                                <div className="mb-4 p-4 bg-red-50 text-red-700 rounded flex items-center gap-2 text-right">
                                    <AlertTriangle size={20} /> <span className="flex-1">{error}</span>
                                </div>
                            )}

                            {successMsg && (
                                <div className="mb-4 p-4 bg-green-50 text-green-700 rounded flex items-center gap-2 text-right">
                                    <CheckCircle size={20} /> <span className="flex-1">{successMsg}</span>
                                </div>
                            )}

                            {connection?.connected && (
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded border border-gray-100 text-right">
                                    <div>
                                        <span className="text-xs text-gray-500 uppercase tracking-wider">متصل باسم</span>
                                        <p className="font-medium text-gray-800">{connection.fb_user_name}</p>
                                    </div>
                                    <div>
                                        <span className="text-xs text-gray-500 uppercase tracking-wider">الصفحات المتاحة</span>
                                        <p className="font-medium text-gray-800">{pages.length}</p>
                                    </div>
                                    <div>
                                        <span className="text-xs text-gray-500 uppercase tracking-wider">الحالة</span>
                                        <p className="flex items-center gap-1 text-green-600 font-medium justify-end md:justify-start">
                                            <CheckCircle size={16} /> نشط
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Mapping Section */}
                        {connection?.connected && (
                            <div className="bg-white rounded-lg shadow p-6 text-right">
                                <h3 className="text-lg font-bold text-gray-800 mb-4">ربط الحقول (Mapping)</h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">اختر الصفحة</label>
                                        <select
                                            className="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                                            onChange={handlePageSelect}
                                            value={selectedPage?.id || ''}
                                        >
                                            <option value="">-- اختر صفحة --</option>
                                            {pages.map(page => (
                                                <option key={page.id} value={page.id}>{page.name}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">اختر النموذج (Lead Form)</label>
                                        <select
                                            className="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                                            onChange={handleFormSelect}
                                            value={selectedForm?.id || ''}
                                            disabled={!selectedPage}
                                        >
                                            <option value="">-- اختر نموذج --</option>
                                            {forms.map(form => (
                                                <option key={form.id} value={form.id}>
                                                    {form.name} ({form.status === 'ACTIVE' ? 'نشط' : form.status})
                                                    {form.is_mapped ? ' ✅ مربوط' : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                {selectedForm && formFields.length > 0 && (
                                    <div className="border-t pt-6">
                                        <h4 className="font-medium text-gray-800 mb-4">تعيين الحقول لنموذج "{selectedForm.name}"</h4>

                                        <div className="space-y-4">
                                            {formFields.map(field => (
                                                <div key={field.name} className="flex flex-col sm:flex-row items-center gap-4 p-3 bg-gray-50 rounded border border-gray-100">
                                                    <div className="w-full sm:w-1/3 text-right">
                                                        <p className="font-medium text-gray-700">{field.label}</p>
                                                        <p className="text-xs text-gray-500 font-mono text-left sm:text-right" dir="ltr">{field.name} ({field.type})</p>
                                                    </div>

                                                    <div className="flex items-center justify-center text-gray-400 rotate-90 sm:rotate-0">
                                                        <ArrowRight />
                                                    </div>

                                                    <div className="w-full sm:w-1/2">
                                                        <select
                                                            className="w-full p-2 border rounded text-sm bg-white"
                                                            onChange={(e) => handleMappingChange(field.name, 'core', e.target.value)}
                                                            value={mappings[field.name]?.value || ''}
                                                        >
                                                            <option value="">-- لا تقم بالاستيراد --</option>
                                                            <optgroup label="الحقول الأساسية">
                                                                {wpFields.map(wp => (
                                                                    <option key={wp.value} value={wp.value}>{wp.label}</option>
                                                                ))}
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="mt-6 flex justify-end">
                                            <button
                                                onClick={saveMapping}
                                                disabled={saving}
                                                className="flex items-center gap-2 px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition disabled:opacity-50"
                                            >
                                                {saving ? <Loader2 className="animate-spin" /> : <CheckCircle size={18} />}
                                                حفظ التعيين
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                    </div>
                </div>
            </main>

            {/* Bottom Navigation for Mobile */}
            <BottomNav />
        </div>
    );
};

export default FacebookLeadsSettings;

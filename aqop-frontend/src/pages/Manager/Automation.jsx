import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Plus,
    Play,
    Edit2,
    Trash2,
    Activity,
    CheckCircle,
    XCircle,
    AlertCircle,
    MoreVertical,
    Save,
    X,
    Clock,
    Zap,
    MessageSquare,
    Filter,
    ArrowRight
} from 'lucide-react';
import Navigation from '../../components/Navigation';
import BottomNav from '../../components/BottomNav';
import { automationApi } from '../../api/automation';
import LoadingSpinner from '../../components/LoadingSpinner';

const Automation = () => {
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('rules');
    const [rules, setRules] = useState([]);
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingRule, setEditingRule] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        trigger_type: 'new_lead',
        trigger_entity: 'lead',
        action_type: 'assign_round_robin',
        action_config: {},
        is_active: true
    });

    useEffect(() => {
        fetchRules();
        fetchLogs();
    }, []);

    const fetchRules = async () => {
        try {
            const response = await automationApi.getRules();
            setRules(response.data || []);
        } catch (error) {
            console.error('Error fetching rules:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchLogs = async () => {
        try {
            const response = await automationApi.getLogs();
            setLogs(response.data || []);
        } catch (error) {
            console.error('Error fetching logs:', error);
        }
    };

    const handleCreateRule = () => {
        setEditingRule(null);
        setFormData({
            name: '',
            trigger_type: 'new_lead',
            trigger_entity: 'lead',
            action_type: 'assign_round_robin',
            action_config: {},
            is_active: true
        });
        setShowModal(true);
    };

    const handleEditRule = (rule) => {
        setEditingRule(rule);
        setFormData({
            name: rule.name,
            trigger_type: rule.trigger_type,
            trigger_entity: rule.trigger_entity,
            action_type: rule.action_type,
            action_config: rule.action_config || {},
            is_active: rule.is_active
        });
        setShowModal(true);
    };

    const handleToggleActive = async (rule) => {
        try {
            await automationApi.toggleRule(rule.id, !rule.is_active);
            fetchRules();
        } catch (error) {
            console.error('Error toggling rule:', error);
        }
    };

    const handleDeleteRule = async (id) => {
        if (!window.confirm('هل أنت متأكد من حذف هذه القاعدة؟')) return;
        try {
            await automationApi.deleteRule(id);
            fetchRules();
        } catch (error) {
            console.error('Error deleting rule:', error);
        }
    };

    const handleModalSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingRule) {
                await automationApi.updateRule(editingRule.id, formData);
            } else {
                await automationApi.createRule(formData);
            }
            setShowModal(false);
            fetchRules();
        } catch (error) {
            console.error('Error saving rule:', error);
            alert('حدث خطأ أثناء حفظ القاعدة');
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 bg-slate-50">
            {/* Sidebar */}
            <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
                <Navigation currentPage="automation" />
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden">
                <Navigation currentPage="automation" />
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
                        <h1 className="text-lg font-bold text-slate-900">الأتمتة</h1>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Desktop Header */}
                    <div className="hidden lg:flex justify-between items-center mb-8">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">الأتمتة</h1>
                            <p className="mt-1 text-slate-600">إدارة قواعد التوزيع التلقائي والردود</p>
                        </div>
                        <button
                            onClick={handleCreateRule}
                            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            <Plus size={20} />
                            <span>قاعدة جديدة</span>
                        </button>
                    </div>

                    {/* Mobile Create Button */}
                    <div className="lg:hidden mb-6">
                        <button
                            onClick={handleCreateRule}
                            className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition font-bold"
                        >
                            <Plus size={20} />
                            <span>قاعدة جديدة</span>
                        </button>
                    </div>

                    {/* Tabs */}
                    <div className="flex gap-4 border-b border-slate-200 mb-6 overflow-x-auto pb-2">
                        <button
                            onClick={() => setActiveTab('rules')}
                            className={`flex items-center gap-2 px-4 py-2 rounded-lg whitespace-nowrap transition-colors ${activeTab === 'rules'
                                ? 'bg-blue-50 text-blue-700 font-medium'
                                : 'text-slate-600 hover:bg-slate-50'
                                }`}
                        >
                            <Zap size={18} />
                            قواعد التوزيع
                        </button>
                        <button
                            onClick={() => setActiveTab('logs')}
                            className={`flex items-center gap-2 px-4 py-2 rounded-lg whitespace-nowrap transition-colors ${activeTab === 'logs'
                                ? 'bg-blue-50 text-blue-700 font-medium'
                                : 'text-slate-600 hover:bg-slate-50'
                                }`}
                        >
                            <Activity size={18} />
                            سجل النشاط
                        </button>
                    </div>

                    {/* Content */}
                    {loading ? (
                        <div className="flex justify-center py-12">
                            <LoadingSpinner size="lg" text="جاري التحميل..." />
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {activeTab === 'rules' ? (
                                <div className="grid gap-4">
                                    {rules.length === 0 ? (
                                        <div className="text-center py-12 bg-white rounded-xl border border-dashed border-slate-300">
                                            <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <Zap className="text-slate-400" size={32} />
                                            </div>
                                            <h3 className="text-lg font-medium text-slate-900">لا توجد قواعد</h3>
                                            <p className="text-slate-500 mt-1">قم بإنشاء قاعدة أتمتة جديدة للبدء</p>
                                        </div>
                                    ) : (
                                        rules.map((rule) => (
                                            <div key={rule.id} className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex items-start gap-3">
                                                        <div className={`p-2 rounded-lg ${rule.is_active ? 'bg-green-50 text-green-600' : 'bg-slate-100 text-slate-500'}`}>
                                                            <Zap size={20} />
                                                        </div>
                                                        <div>
                                                            <h3 className="font-bold text-slate-900">{rule.name}</h3>
                                                            <div className="flex flex-wrap gap-2 mt-2">
                                                                <span className="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-md font-medium">
                                                                    {rule.trigger_type === 'new_lead' ? 'ليد جديد' : rule.trigger_type}
                                                                </span>
                                                                <span className="text-slate-400 text-xs flex items-center">
                                                                    <ArrowRight size={12} className="mx-1" />
                                                                </span>
                                                                <span className="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-md font-medium">
                                                                    {rule.action_type === 'assign_round_robin' ? 'توزيع دوري' : rule.action_type}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <button
                                                            onClick={() => handleToggleActive(rule)}
                                                            className={`p-2 rounded-lg transition-colors ${rule.is_active ? 'text-green-600 bg-green-50 hover:bg-green-100' : 'text-slate-400 bg-slate-50 hover:bg-slate-100'}`}
                                                            title={rule.is_active ? 'إيقاف' : 'تفعيل'}
                                                        >
                                                            {rule.is_active ? <Play size={18} /> : <XCircle size={18} />}
                                                        </button>
                                                        <button
                                                            onClick={() => handleEditRule(rule)}
                                                            className="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
                                                            title="تعديل"
                                                        >
                                                            <Edit2 size={18} />
                                                        </button>
                                                        <button
                                                            onClick={() => handleDeleteRule(rule.id)}
                                                            className="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                                                            title="حذف"
                                                        >
                                                            <Trash2 size={18} />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            ) : (
                                <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm text-right">
                                            <thead className="bg-slate-50 text-slate-500 font-medium">
                                                <tr>
                                                    <th className="px-4 py-3">الوقت</th>
                                                    <th className="px-4 py-3">الحدث</th>
                                                    <th className="px-4 py-3">القاعدة</th>
                                                    <th className="px-4 py-3">الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {logs.map((log) => (
                                                    <tr key={log.id} className="hover:bg-slate-50">
                                                        <td className="px-4 py-3 text-slate-500 font-mono text-xs">
                                                            {new Date(log.created_at).toLocaleString('en-US')}
                                                        </td>
                                                        <td className="px-4 py-3 text-slate-900">{log.entity_description || '-'}</td>
                                                        <td className="px-4 py-3 text-slate-600">{log.rule_name}</td>
                                                        <td className="px-4 py-3">
                                                            <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${log.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                                {log.status === 'success' ? 'نجاح' : 'فشل'}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                ))}
                                                {logs.length === 0 && (
                                                    <tr>
                                                        <td colSpan="4" className="px-4 py-8 text-center text-slate-500">
                                                            لا توجد سجلات نشاط
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Create/Edit Modal */}
                {showModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                        <div className="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                            <div className="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                                <h2 className="text-xl font-bold text-slate-900">
                                    {editingRule ? 'تعديل قاعدة' : 'قاعدة جديدة'}
                                </h2>
                                <button
                                    onClick={() => setShowModal(false)}
                                    className="p-2 hover:bg-slate-200 rounded-full transition-colors text-slate-500"
                                >
                                    <X size={20} />
                                </button>
                            </div>

                            <div className="p-6 overflow-y-auto">
                                <form onSubmit={handleModalSubmit} className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-2">اسم القاعدة</label>
                                        <input
                                            type="text"
                                            value={formData.name}
                                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                            className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                            placeholder="مثال: توزيع ليد العقارات"
                                            required
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-slate-700 mb-2">نوع المشغل (Trigger)</label>
                                            <select
                                                value={formData.trigger_type}
                                                onChange={(e) => setFormData({ ...formData, trigger_type: e.target.value })}
                                                className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                                            >
                                                <option value="new_lead">لدى إنشاء ليد جديد</option>
                                                <option value="status_change">تغيير الحالة</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-slate-700 mb-2">العنصر المستهدف</label>
                                            <select
                                                value={formData.trigger_entity}
                                                onChange={(e) => setFormData({ ...formData, trigger_entity: e.target.value })}
                                                className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                                            >
                                                <option value="lead">Lead (ليد)</option>
                                            </select>
                                        </div>
                                    </div>

                                    {/* Conditions Section - Placeholder for now */}
                                    <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                        <h3 className="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2">
                                            <Filter size={16} />
                                            الشروط (اختياري)
                                        </h3>
                                        <p className="text-xs text-slate-500 mb-2">سيتم تنفيذ القاعدة فقط إذا تحققت الشروط التالية.</p>
                                        {/* TODO: Add complex condition builder here */}
                                        <div className="text-center py-4 text-sm text-slate-400 italic">
                                            (محرر الشروط قيد التطوير)
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-2">الإجراء (Action)</label>
                                        <select
                                            value={formData.action_type}
                                            onChange={(e) => setFormData({ ...formData, action_type: e.target.value })}
                                            className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                                        >
                                            <option value="assign_round_robin">توزيع دوري (Round Robin)</option>
                                            <option value="assign_weighted">توزيع وزني</option>
                                            <option value="send_whatsapp">إرسال واتساب</option>
                                            <option value="send_email">إرسال بريد إلكتروني</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-2">الإعدادات (JSON)</label>
                                        <textarea
                                            value={JSON.stringify(formData.action_config, null, 2)}
                                            onChange={(e) => {
                                                try {
                                                    setFormData({ ...formData, action_config: JSON.parse(e.target.value) });
                                                } catch (err) {
                                                    // Allow editing invalid JSON while typing
                                                }
                                            }}
                                            className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none font-mono text-xs h-32 text-left"
                                            placeholder="{}"
                                            dir="ltr"
                                        />
                                        <p className="text-xs text-slate-500 mt-1">تكوين خاص للإجراء (مثل معرفات المستخدمين للتوزيع)</p>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id="is_active"
                                            checked={formData.is_active}
                                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                            className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <label htmlFor="is_active" className="text-sm font-medium text-slate-700">تفعيل القاعدة فوراً</label>
                                    </div>

                                    <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                                        <button
                                            type="button"
                                            onClick={() => setShowModal(false)}
                                            className="px-6 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition"
                                        >
                                            إلغاء
                                        </button>
                                        <button
                                            type="submit"
                                            className="px-6 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm hover:shadow transition"
                                        >
                                            {editingRule ? 'حفظ التغييرات' : 'إنشاء القاعدة'}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                )}
            </main>

            {/* Bottom Navigation for Mobile */}
            <BottomNav />
        </div>
    );
};

export default Automation;

import React, { useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useCreateFeedback } from '../hooks/useFeedback';
import { MessageCircle, X, Send, Bug, Lightbulb, AlertCircle, HelpCircle } from 'lucide-react';

export default function FeedbackWidget() {
    const [isOpen, setIsOpen] = useState(false);
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        feedback_type: 'question',
        priority: 'medium',
    });

    const location = useLocation();
    const { mutate: createFeedback, isPending } = useCreateFeedback();

    // Detect current module from route
    const getCurrentModule = () => {
        const path = location.pathname;
        if (path.includes('/leads')) return 'leads';
        if (path.includes('/feedback')) return 'feedback';
        if (path.includes('/analytics')) return 'analytics';
        if (path.includes('/users')) return 'users';
        return 'general';
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        const feedbackData = {
            ...formData,
            module_code: getCurrentModule(),
            page_url: window.location.href,
            browser_info: navigator.userAgent,
        };

        createFeedback(feedbackData, {
            onSuccess: () => {
                setFormData({
                    title: '',
                    description: '',
                    feedback_type: 'question',
                    priority: 'medium',
                });
                setIsOpen(false);
                // Show success message
                alert('شكراً لك! تم إرسال ملاحظتك بنجاح.');
            },
            onError: () => {
                alert('حدث خطأ أثناء إرسال الملاحظة. يرجى المحاولة مرة أخرى.');
            },
        });
    };

    const typeIcons = {
        bug: <Bug className="w-5 h-5" />,
        feature_request: <Lightbulb className="w-5 h-5" />,
        improvement: <AlertCircle className="w-5 h-5" />,
        question: <HelpCircle className="w-5 h-5" />,
    };

    return (
        <>
            {/* Floating Button */}
            <button
                onClick={() => setIsOpen(true)}
                className="fixed bottom-6 right-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 z-50 group"
                aria-label="Send Feedback"
            >
                <MessageCircle className="w-6 h-6" />
                <span className="absolute right-full mr-3 top-1/2 -translate-y-1/2 bg-gray-900 text-white text-sm px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                    إرسال ملاحظة
                </span>
            </button>

            {/* Modal */}
            {isOpen && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 rounded-t-2xl flex items-center justify-between">
                            <div className="flex items-center space-x-3 space-x-reverse">
                                <MessageCircle className="w-6 h-6" />
                                <h2 className="text-xl font-bold">إرسال ملاحظة أو مشكلة</h2>
                            </div>
                            <button
                                onClick={() => setIsOpen(false)}
                                className="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Form */}
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Type Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-3">نوع الملاحظة</label>
                                <div className="grid grid-cols-2 gap-3">
                                    {[
                                        { value: 'bug', label: 'مشكلة تقنية', color: 'red' },
                                        { value: 'feature_request', label: 'طلب ميزة', color: 'blue' },
                                        { value: 'improvement', label: 'تحسين', color: 'orange' },
                                        { value: 'question', label: 'استفسار', color: 'gray' },
                                    ].map((type) => (
                                        <button
                                            key={type.value}
                                            type="button"
                                            onClick={() => setFormData({ ...formData, feedback_type: type.value })}
                                            className={`flex items-center justify-center space-x-2 space-x-reverse p-3 rounded-lg border-2 transition-all ${formData.feedback_type === type.value
                                                    ? `border-${type.color}-500 bg-${type.color}-50`
                                                    : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                        >
                                            {typeIcons[type.value]}
                                            <span className="font-medium">{type.label}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Priority */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">الأولوية</label>
                                <select
                                    value={formData.priority}
                                    onChange={(e) => setFormData({ ...formData, priority: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="low">منخفضة</option>
                                    <option value="medium">متوسطة</option>
                                    <option value="high">عالية</option>
                                    <option value="critical">حرجة</option>
                                </select>
                            </div>

                            {/* Title */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">العنوان *</label>
                                <input
                                    type="text"
                                    value={formData.title}
                                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                    required
                                    placeholder="اكتب عنواناً مختصراً للملاحظة"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">التفاصيل *</label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    required
                                    rows={5}
                                    placeholder="اشرح الملاحظة أو المشكلة بالتفصيل..."
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                />
                            </div>

                            {/* Context Info */}
                            <div className="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                                <p className="font-medium mb-2">سيتم إرسال المعلومات التالية تلقائياً:</p>
                                <ul className="space-y-1">
                                    <li>• الصفحة الحالية: {location.pathname}</li>
                                    <li>• الموديول: {getCurrentModule()}</li>
                                    <li>• معلومات المتصفح</li>
                                </ul>
                            </div>

                            {/* Submit Button */}
                            <div className="flex items-center justify-end space-x-3 space-x-reverse pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => setIsOpen(false)}
                                    className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    disabled={isPending}
                                    className="flex items-center space-x-2 space-x-reverse px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Send className="w-4 h-4" />
                                    <span>{isPending ? 'جاري الإرسال...' : 'إرسال الملاحظة'}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

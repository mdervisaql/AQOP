import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../auth/AuthContext';
import Navigation from '../../components/Navigation';
import BottomNav from '../../components/BottomNav';
import LoadingSpinner from '../../components/LoadingSpinner';
import { getBulkJobs, getBulkJobResults, cancelBulkJob, exportBulkJobResults } from '../../api/whatsapp';

export default function BulkWhatsAppJobs() {
    const navigate = useNavigate();
    const { user } = useAuth();
    const [jobs, setJobs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedJob, setSelectedJob] = useState(null);
    const [jobResults, setJobResults] = useState([]);
    const [loadingResults, setLoadingResults] = useState(false);
    const [showResultsModal, setShowResultsModal] = useState(false);

    useEffect(() => {
        fetchJobs();
        const interval = setInterval(fetchJobs, 10000); // Poll every 10s
        return () => clearInterval(interval);
    }, []);

    const fetchJobs = async () => {
        try {
            const response = await getBulkJobs({ limit: 50 });
            setJobs(response || []);
            setError(null);
        } catch (err) {
            console.error('Error fetching jobs:', err);
            setError('فشل تحميل المهام');
        } finally {
            setLoading(false);
        }
    };

    const handleViewResults = async (job) => {
        setSelectedJob(job);
        setShowResultsModal(true);
        setLoadingResults(true);
        try {
            const results = await getBulkJobResults(job.id);
            setJobResults(results || []);
        } catch (err) {
            console.error('Error fetching results:', err);
            alert('فشل تحميل النتائج');
        } finally {
            setLoadingResults(false);
        }
    };

    const handleCancelJob = async (jobId) => {
        if (!window.confirm('هل أنت متأكد من إلغاء هذه المهمة؟')) return;
        try {
            await cancelBulkJob(jobId);
            fetchJobs();
            alert('تم إلغاء المهمة');
        } catch (err) {
            console.error('Error cancelling job:', err);
            alert('فشل إلغاء المهمة');
        }
    };

    const handleExport = async (jobId) => {
        try {
            const response = await exportBulkJobResults(jobId);
            const url = window.URL.createObjectURL(new Blob([response]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `job_${jobId}_results.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (err) {
            console.error('Error exporting results:', err);
            alert('فشل تصدير النتائج');
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'completed': return 'bg-green-100 text-green-800';
            case 'processing': return 'bg-blue-100 text-blue-800';
            case 'failed': return 'bg-red-100 text-red-800';
            case 'cancelled': return 'bg-gray-100 text-gray-800';
            default: return 'bg-yellow-100 text-yellow-800';
        }
    };

    const getStatusLabel = (status) => {
        const labels = {
            'completed': 'مكتمل',
            'processing': 'جاري',
            'failed': 'فشل',
            'cancelled': 'ملغي',
            'pending': 'معلق',
        };
        return labels[status] || status;
    };

    return (
        <div className="min-h-screen bg-gray-50 bg-slate-50">
            {/* Desktop Sidebar */}
            <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
                <Navigation currentPage="bulk-whatsapp" />
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden">
                <Navigation currentPage="bulk-whatsapp" />
            </div>

            {/* Main Content */}
            <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
                {/* Mobile Header with Back Button */}
                <div className="lg:hidden bg-white px-4 py-3 border-b sticky top-14 z-10">
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => navigate(-1)}
                            className="p-2 hover:bg-gray-100 rounded-lg"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div>
                            <h1 className="text-lg font-bold text-gray-900">رسائل واتساب الجماعية</h1>
                            <p className="text-xs text-gray-500">{jobs.length} مهمة</p>
                        </div>
                    </div>
                </div>

                {/* Desktop Header */}
                <div className="hidden lg:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <h1 className="text-2xl font-bold text-gray-900">رسائل واتساب الجماعية</h1>
                    <p className="mt-1 text-gray-600">إدارة ومراقبة حملات الرسائل الجماعية</p>
                </div>

                {/* Content */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {loading ? (
                        <div className="flex justify-center py-12">
                            <LoadingSpinner size="lg" text="جاري تحميل المهام..." />
                        </div>
                    ) : error ? (
                        <div className="bg-red-50 p-4 rounded-xl text-red-700">{error}</div>
                    ) : jobs.length === 0 ? (
                        <div className="text-center py-12 bg-white rounded-xl">
                            <span className="text-5xl">📨</span>
                            <p className="text-gray-900 font-medium mt-4">لا توجد مهام</p>
                            <p className="text-gray-500 text-sm mt-1">سيظهر هنا حملات الرسائل الجماعية</p>
                        </div>
                    ) : (
                        <>
                            {/* Mobile Jobs List */}
                            <div className="lg:hidden space-y-3">
                                {jobs.map((job) => (
                                    <div key={job.id} className="bg-white rounded-xl p-4">
                                        <div className="flex items-start justify-between mb-3">
                                            <div>
                                                <p className="font-bold text-gray-900">{job.job_name}</p>
                                                <p className="text-xs text-gray-500">{job.message_type}</p>
                                            </div>
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(job.status)}`}>
                                                {getStatusLabel(job.status)}
                                            </span>
                                        </div>

                                        {/* Progress */}
                                        <div className="mb-3">
                                            <div className="flex items-center justify-between text-xs text-gray-500 mb-1">
                                                <span>التقدم</span>
                                                <span>{job.sent_count + job.failed_count} / {job.total_count}</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-blue-600 h-2 rounded-full"
                                                    style={{ width: `${Math.min(100, ((job.sent_count + job.failed_count) / job.total_count) * 100)}%` }}
                                                ></div>
                                            </div>
                                        </div>

                                        {/* Actions */}
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => handleViewResults(job)}
                                                className="flex-1 text-center py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium"
                                            >
                                                النتائج
                                            </button>
                                            <button
                                                onClick={() => handleExport(job.id)}
                                                className="flex-1 text-center py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium"
                                            >
                                                تصدير
                                            </button>
                                            {(job.status === 'pending' || job.status === 'processing') && (
                                                <button
                                                    onClick={() => handleCancelJob(job.id)}
                                                    className="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium"
                                                >
                                                    إلغاء
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Desktop Table */}
                            <div className="hidden lg:block bg-white shadow overflow-hidden sm:rounded-lg">
                                <table className="min-w-full divide-y divide-gray-200 text-right">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم المهمة</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التقدم</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الإنشاء</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {jobs.map((job) => (
                                            <tr key={job.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900">{job.job_name}</div>
                                                    <div className="text-sm text-gray-500">{job.message_type}</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(job.status)}`}>
                                                        {getStatusLabel(job.status)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-900">
                                                        {job.sent_count + job.failed_count} / {job.total_count}
                                                    </div>
                                                    <div className="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                                                        <div
                                                            className="bg-blue-600 h-2.5 rounded-full"
                                                            style={{ width: `${Math.min(100, ((job.sent_count + job.failed_count) / job.total_count) * 100)}%` }}
                                                        ></div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {new Date(job.created_at).toLocaleString('en-GB')}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                                    <button
                                                        onClick={() => handleViewResults(job)}
                                                        className="text-indigo-600 hover:text-indigo-900 ml-4"
                                                    >
                                                        النتائج
                                                    </button>
                                                    <button
                                                        onClick={() => handleExport(job.id)}
                                                        className="text-green-600 hover:text-green-900 ml-4"
                                                    >
                                                        تصدير
                                                    </button>
                                                    {(job.status === 'pending' || job.status === 'processing') && (
                                                        <button
                                                            onClick={() => handleCancelJob(job.id)}
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            إلغاء
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {jobs.length === 0 && (
                                            <tr>
                                                <td colSpan="5" className="px-6 py-4 text-center text-gray-500">
                                                    لا توجد مهام.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}
                </div>
            </main>

            {/* Results Modal */}
            {showResultsModal && selectedJob && (
                <div className="fixed inset-0 z-50 overflow-y-auto" dir="rtl">
                    <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 transition-opacity" aria-hidden="true" onClick={() => setShowResultsModal(false)}>
                            <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>

                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div className="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                            <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-right w-full">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            نتائج المهمة: {selectedJob.job_name}
                                        </h3>

                                        {loadingResults ? (
                                            <div className="flex justify-center py-8">
                                                <LoadingSpinner text="جاري تحميل النتائج..." />
                                            </div>
                                        ) : (
                                            <div className="overflow-x-auto max-h-96">
                                                <table className="min-w-full divide-y divide-gray-200">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">العميل</th>
                                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">الهاتف</th>
                                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">خطأ</th>
                                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">وقت الإرسال</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="bg-white divide-y divide-gray-200">
                                                        {jobResults.map((result) => (
                                                            <tr key={result.id}>
                                                                <td className="px-4 py-2 text-sm text-gray-900">{result.lead_name}</td>
                                                                <td className="px-4 py-2 text-sm text-gray-500" dir="ltr">{result.phone_number}</td>
                                                                <td className="px-4 py-2 text-sm">
                                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${result.status === 'sent' ? 'bg-green-100 text-green-800' :
                                                                        result.status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                                                        }`}>
                                                                        {result.status === 'sent' ? 'تم الإرسال' : result.status === 'failed' ? 'فشل' : result.status}
                                                                    </span>
                                                                </td>
                                                                <td className="px-4 py-2 text-sm text-red-500">{result.error_message}</td>
                                                                <td className="px-4 py-2 text-sm text-gray-500">{result.sent_at}</td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={() => setShowResultsModal(false)}
                                    className="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    إغلاق
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Bottom Navigation for Mobile */}
            <BottomNav />
        </div>
    );
}

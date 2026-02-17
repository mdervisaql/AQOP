import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getTodayFollowUps, completeFollowUp } from '../api/communications';
import { formatDateTime } from '../utils/helpers';
import LoadingSpinner from './LoadingSpinner';

export default function FollowUpWidget() {
    const [followUps, setFollowUps] = useState([]);
    const [loading, setLoading] = useState(true);
    const [completing, setCompleting] = useState(null);

    const fetchFollowUps = async () => {
        setLoading(true);
        try {
            const data = await getTodayFollowUps();
            setFollowUps(data || []);
        } catch (error) {
            console.error('Error loading follow-ups:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchFollowUps();
    }, []);

    const handleComplete = async (id) => {
        setCompleting(id);
        try {
            await completeFollowUp(id);
            setFollowUps((prev) => prev.filter((f) => f.id !== id));
        } catch (error) {
            console.error('Error completing follow-up:', error);
        } finally {
            setCompleting(null);
        }
    };

    if (loading) {
        return (
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 h-full flex items-center justify-center">
                <LoadingSpinner size="sm" />
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 h-full">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span className="text-xl">📅</span> متابعات اليوم
                </h3>
                <div className="flex items-center gap-2">
                    <Link to="/follow-ups" className="text-xs text-blue-600 hover:text-blue-800 font-medium">
                        عرض الكل
                    </Link>
                    <span className="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        {followUps.length}
                    </span>
                </div>
            </div>

            {followUps.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                    <p>لا توجد متابعات مجدولة لليوم</p>
                    <p className="text-sm mt-1">عمل رائع! 🎉</p>
                </div>
            ) : (
                <div className="space-y-3 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                    {followUps.map((task) => (
                        <div key={task.id} className="flex items-start justify-between p-3 bg-gray-50 rounded-lg border border-gray-100 hover:border-blue-200 transition-colors">
                            <div className="min-w-0 flex-1 mr-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <span className="text-xs font-medium text-gray-500">
                                        {formatDateTime(task.due_date).split(',')[1]}
                                    </span>
                                    <Link
                                        to={`/leads/${task.lead_id}`}
                                        className="text-sm font-medium text-blue-600 hover:text-blue-800 truncate"
                                    >
                                        {task.lead_name}
                                    </Link>
                                </div>
                                <p className="text-sm font-medium text-gray-900 truncate">{task.title}</p>
                                {task.description && (
                                    <p className="text-xs text-gray-500 mt-0.5 line-clamp-1">{task.description}</p>
                                )}
                            </div>
                            <button
                                onClick={() => handleComplete(task.id)}
                                disabled={completing === task.id}
                                className="flex-shrink-0 p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-full transition-colors"
                                title="Mark as Complete"
                            >
                                {completing === task.id ? (
                                    <div className="w-5 h-5 border-2 border-gray-300 border-t-green-600 rounded-full animate-spin"></div>
                                ) : (
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                )}
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

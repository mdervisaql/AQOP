import { useState, useEffect } from 'react';
import { getCommunications } from '../api/communications';
import { formatDateTime } from '../utils/helpers';
import AddCommunicationForm from './AddCommunicationForm';
import LoadingSpinner from './LoadingSpinner';

const TYPE_ICONS = {
    call: '📞',
    whatsapp: '📱',
    email: '📧',
    sms: '💬',
    meeting: '🤝',
    note: '📝',
};

const OUTCOME_COLORS = {
    interested: 'bg-green-100 text-green-800',
    not_interested: 'bg-red-100 text-red-800',
    callback: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-blue-100 text-blue-800',
    no_answer: 'bg-gray-100 text-gray-800',
    busy: 'bg-gray-100 text-gray-800',
    voicemail: 'bg-gray-100 text-gray-800',
    answered: 'bg-blue-50 text-blue-600',
};

export default function CommunicationLog({ leadId }) {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showAddForm, setShowAddForm] = useState(false);

    const fetchLogs = async () => {
        setLoading(true);
        try {
            const data = await getCommunications(leadId);
            setLogs(data || []);
        } catch (error) {
            console.error('Error loading logs:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchLogs();
    }, [leadId]);

    const handleAddSuccess = () => {
        setShowAddForm(false);
        fetchLogs();
    };

    if (loading && !logs.length) {
        return <LoadingSpinner size="sm" />;
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">Communication History</h3>
                {!showAddForm && (
                    <button
                        onClick={() => setShowAddForm(true)}
                        className="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        + Log Communication
                    </button>
                )}
            </div>

            {showAddForm && (
                <AddCommunicationForm
                    leadId={leadId}
                    onAdd={handleAddSuccess}
                    onCancel={() => setShowAddForm(false)}
                />
            )}

            <div className="flow-root">
                <ul className="-mb-8">
                    {logs.length === 0 ? (
                        <li className="text-center py-8 text-gray-500">No communication logs yet.</li>
                    ) : (
                        logs.map((log, logIdx) => (
                            <li key={log.id}>
                                <div className="relative pb-8">
                                    {logIdx !== logs.length - 1 ? (
                                        <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true" />
                                    ) : null}
                                    <div className="relative flex space-x-3">
                                        <div className="relative">
                                            <span className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center ring-8 ring-white text-lg">
                                                {TYPE_ICONS[log.type] || '📝'}
                                            </span>
                                        </div>
                                        <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <div className="text-sm text-gray-500">
                                                    <span className="font-medium text-gray-900">{log.user_name}</span> logged a{' '}
                                                    <span className="font-medium text-gray-900 capitalize">{log.type}</span>
                                                    {log.direction === 'inbound' && <span className="ml-1 text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">Inbound</span>}
                                                </div>

                                                {log.subject && (
                                                    <p className="text-sm font-medium text-gray-900 mt-1">{log.subject}</p>
                                                )}

                                                <p className="text-sm text-gray-600 mt-1 whitespace-pre-wrap">{log.content}</p>

                                                {log.follow_up_date && (
                                                    <div className="mt-2 text-xs text-orange-600 flex items-center gap-1">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        Follow-up: {formatDateTime(log.follow_up_date)}
                                                        {log.follow_up_note && <span className="text-gray-500">- {log.follow_up_note}</span>}
                                                    </div>
                                                )}
                                            </div>
                                            <div className="text-right text-sm whitespace-nowrap text-gray-500">
                                                <div>{formatDateTime(log.created_at)}</div>
                                                {log.outcome && (
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1 ${OUTCOME_COLORS[log.outcome] || 'bg-gray-100 text-gray-800'}`}>
                                                        {log.outcome.replace('_', ' ')}
                                                    </span>
                                                )}
                                                {log.duration_seconds && (
                                                    <div className="text-xs text-gray-400 mt-1">
                                                        {Math.round(log.duration_seconds / 60)} mins
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        ))
                    )}
                </ul>
            </div>
        </div>
    );
}

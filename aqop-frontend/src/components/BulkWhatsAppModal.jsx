import { useState } from 'react';
import { createBulkJob } from '../api/whatsapp';

export default function BulkWhatsAppModal({ isOpen, onClose, selectedLeads, onSuccess }) {
    const [step, setStep] = useState(1);
    const [jobName, setJobName] = useState('');
    const [messageType, setMessageType] = useState('custom');
    const [messageContent, setMessageContent] = useState('');
    const [templateName, setTemplateName] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState('');

    if (!isOpen) return null;

    const placeholders = [
        { key: '{name}', label: 'Full Name' },
        { key: '{first_name}', label: 'First Name' },
        { key: '{phone}', label: 'Phone Number' },
        { key: '{email}', label: 'Email Address' },
    ];

    const insertPlaceholder = (placeholder) => {
        setMessageContent(prev => prev + placeholder);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setIsSubmitting(true);

        try {
            const payload = {
                job_name: jobName || `Bulk Job - ${new Date().toLocaleString()}`,
                lead_ids: selectedLeads,
                message_type: messageType,
                message_content: messageContent,
                template_name: templateName,
            };

            await createBulkJob(payload);
            onSuccess();
            onClose();
        } catch (err) {
            console.error('Failed to create job:', err);
            setError(err.response?.data?.message || 'Failed to create bulk job');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {/* Background overlay */}
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onClick={onClose}></div>

                <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div className="sm:flex sm:items-start">
                            <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg className="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 className="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Send Bulk WhatsApp
                                </h3>
                                <div className="mt-2">
                                    <p className="text-sm text-gray-500 mb-4">
                                        Sending to <span className="font-bold text-gray-900">{selectedLeads.length}</span> selected leads.
                                    </p>

                                    {error && (
                                        <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                                            <span className="block sm:inline">{error}</span>
                                        </div>
                                    )}

                                    <form id="bulk-whatsapp-form" onSubmit={handleSubmit}>
                                        <div className="mb-4">
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Job Name (Optional)</label>
                                            <input
                                                type="text"
                                                value={jobName}
                                                onChange={(e) => setJobName(e.target.value)}
                                                placeholder="e.g. Summer Promo Campaign"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                            />
                                        </div>

                                        <div className="mb-4">
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Message Type</label>
                                            <div className="flex gap-4">
                                                <label className="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        className="form-radio text-green-600"
                                                        name="messageType"
                                                        value="custom"
                                                        checked={messageType === 'custom'}
                                                        onChange={(e) => setMessageType(e.target.value)}
                                                    />
                                                    <span className="ml-2">Custom Message</span>
                                                </label>
                                                <label className="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        className="form-radio text-green-600"
                                                        name="messageType"
                                                        value="template"
                                                        checked={messageType === 'template'}
                                                        onChange={(e) => setMessageType(e.target.value)}
                                                    />
                                                    <span className="ml-2">Template</span>
                                                </label>
                                            </div>
                                        </div>

                                        {messageType === 'custom' ? (
                                            <div className="mb-4">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Message Content</label>
                                                <textarea
                                                    rows="4"
                                                    value={messageContent}
                                                    onChange={(e) => setMessageContent(e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="Type your message here..."
                                                    required
                                                ></textarea>
                                                <div className="mt-2 flex flex-wrap gap-2">
                                                    {placeholders.map(p => (
                                                        <button
                                                            key={p.key}
                                                            type="button"
                                                            onClick={() => insertPlaceholder(p.key)}
                                                            className="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none"
                                                        >
                                                            {p.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="mb-4">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                                                <input
                                                    type="text"
                                                    value={templateName}
                                                    onChange={(e) => setTemplateName(e.target.value)}
                                                    placeholder="e.g. hello_world"
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    required
                                                />
                                                <p className="mt-1 text-xs text-gray-500">
                                                    Enter the exact template name as approved in WhatsApp Manager.
                                                </p>
                                            </div>
                                        )}
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="submit"
                            form="bulk-whatsapp-form"
                            disabled={isSubmitting}
                            className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            {isSubmitting ? 'Creating Job...' : 'Send Messages'}
                        </button>
                        <button
                            type="button"
                            onClick={onClose}
                            className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

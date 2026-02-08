import { useState } from 'react';
import { addCommunication } from '../api/communications';

export default function AddCommunicationForm({ leadId, onAdd, onCancel }) {
    const [type, setType] = useState('call');
    const [direction, setDirection] = useState('outbound');
    const [subject, setSubject] = useState('');
    const [content, setContent] = useState('');
    const [outcome, setOutcome] = useState('');
    const [duration, setDuration] = useState('');
    const [followUpDate, setFollowUpDate] = useState('');
    const [followUpNote, setFollowUpNote] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!content.trim()) return;

        setLoading(true);
        try {
            const data = {
                type,
                direction,
                subject,
                content,
                outcome,
                duration_seconds: duration ? parseInt(duration) * 60 : null, // Convert minutes to seconds
                follow_up_date: followUpDate || null,
                follow_up_note: followUpNote || null,
            };

            await addCommunication(leadId, data);
            onAdd();
        } catch (error) {
            console.error('Failed to add communication:', error);
            alert('Failed to save communication log.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Log Communication</h3>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="call">📞 Call</option>
                        <option value="whatsapp">📱 WhatsApp</option>
                        <option value="email">📧 Email</option>
                        <option value="sms">💬 SMS</option>
                        <option value="meeting">🤝 Meeting</option>
                        <option value="note">📝 Note</option>
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Direction</label>
                    <select
                        value={direction}
                        onChange={(e) => setDirection(e.target.value)}
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="outbound">Outbound (We contacted them)</option>
                        <option value="inbound">Inbound (They contacted us)</option>
                    </select>
                </div>
            </div>

            <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">Subject / Summary</label>
                <input
                    type="text"
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    placeholder="Brief summary..."
                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">Details</label>
                <textarea
                    value={content}
                    onChange={(e) => setContent(e.target.value)}
                    rows={3}
                    required
                    placeholder="What was discussed?"
                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Outcome</label>
                    <select
                        value={outcome}
                        onChange={(e) => setOutcome(e.target.value)}
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Select Outcome...</option>
                        <option value="answered">Answered</option>
                        <option value="no_answer">No Answer</option>
                        <option value="busy">Busy</option>
                        <option value="voicemail">Voicemail</option>
                        <option value="interested">Interested</option>
                        <option value="not_interested">Not Interested</option>
                        <option value="callback">Callback Requested</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                {type === 'call' && (
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                        <input
                            type="number"
                            min="0"
                            value={duration}
                            onChange={(e) => setDuration(e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                )}
            </div>

            <div className="border-t border-gray-200 pt-4 mt-4">
                <h4 className="text-sm font-medium text-gray-900 mb-3">Schedule Follow-up (Optional)</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Date & Time</label>
                        <input
                            type="datetime-local"
                            value={followUpDate}
                            onChange={(e) => setFollowUpDate(e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Follow-up Note</label>
                        <input
                            type="text"
                            value={followUpNote}
                            onChange={(e) => setFollowUpNote(e.target.value)}
                            placeholder="What to do next?"
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>

            <div className="flex justify-end gap-3 mt-6">
                <button
                    type="button"
                    onClick={onCancel}
                    className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    disabled={loading}
                    className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                >
                    {loading ? 'Saving...' : 'Save Log'}
                </button>
            </div>
        </form>
    );
}

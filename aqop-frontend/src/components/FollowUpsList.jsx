import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getFollowUps, completeFollowUp } from '../api/communications';
import { formatDateTime } from '../utils/helpers';
import LoadingSpinner from './LoadingSpinner';

export default function FollowUpsList() {
    const [followUps, setFollowUps] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('pending'); // pending, completed, all

    const fetchFollowUps = async () => {
        setLoading(true);
        try {
            const data = await getFollowUps({ status: filter });
            setFollowUps(data || []);
        } catch (error) {
            console.error('Error loading follow-ups:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchFollowUps();
    }, [filter]);

    const handleComplete = async (id) => {
        try {
            await completeFollowUp(id);
            // Refresh list
            fetchFollowUps();
        } catch (error) {
            console.error('Error completing follow-up:', error);
        }
    };

    return (
        <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-semibold text-gray-900">Follow-up Tasks</h2>
                <div className="flex gap-2">
                    <button
                        onClick={() => setFilter('pending')}
                        className={`px-3 py-1.5 text-sm font-medium rounded-md ${filter === 'pending'
                                ? 'bg-blue-100 text-blue-700'
                                : 'text-gray-600 hover:bg-gray-100'
                            }`}
                    >
                        Pending
                    </button>
                    <button
                        onClick={() => setFilter('completed')}
                        className={`px-3 py-1.5 text-sm font-medium rounded-md ${filter === 'completed'
                                ? 'bg-green-100 text-green-700'
                                : 'text-gray-600 hover:bg-gray-100'
                            }`}
                    >
                        Completed
                    </button>
                    <button
                        onClick={() => setFilter('all')}
                        className={`px-3 py-1.5 text-sm font-medium rounded-md ${filter === 'all'
                                ? 'bg-gray-100 text-gray-700'
                                : 'text-gray-600 hover:bg-gray-100'
                            }`}
                    >
                        All
                    </button>
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center py-8">
                    <LoadingSpinner size="md" />
                </div>
            ) : followUps.length === 0 ? (
                <div className="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                    <p className="text-gray-500">No tasks found.</p>
                </div>
            ) : (
                <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-300">
                        <thead className="bg-gray-50">
                            <tr>
                                <th scope="col" className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Status</th>
                                <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Due Date</th>
                                <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Lead</th>
                                <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Task</th>
                                <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {followUps.map((task) => (
                                <tr key={task.id}>
                                    <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${task.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                task.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                                    'bg-yellow-100 text-yellow-800'
                                            }`}>
                                            {task.status}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {formatDateTime(task.due_date)}
                                    </td>
                                    <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <Link to={`/leads/${task.lead_id}`} className="text-blue-600 hover:text-blue-900">
                                            {task.lead_name}
                                        </Link>
                                        <div className="text-xs text-gray-400">{task.lead_phone}</div>
                                    </td>
                                    <td className="px-3 py-4 text-sm text-gray-500">
                                        <div className="font-medium text-gray-900">{task.title}</div>
                                        <div className="text-gray-500">{task.description}</div>
                                    </td>
                                    <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        {task.status === 'pending' && (
                                            <button
                                                onClick={() => handleComplete(task.id)}
                                                className="text-green-600 hover:text-green-900"
                                            >
                                                Complete
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

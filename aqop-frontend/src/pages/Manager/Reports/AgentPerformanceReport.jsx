import React, { useState, useEffect } from 'react';
import { getAgentPerformanceReport } from '../../../api/reports';
import PerformanceChart from '../../../components/charts/PerformanceChart';
import { Loader2, Download } from 'lucide-react';

const AgentPerformanceReport = ({ dateRange }) => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchData();
    }, [dateRange]);

    const fetchData = async () => {
        try {
            setLoading(true);
            const response = await getAgentPerformanceReport({
                from: dateRange.from,
                to: dateRange.to
            });
            setData(response.data);
        } catch (err) {
            setError('Failed to load agent performance data');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div className="flex justify-center p-8"><Loader2 className="animate-spin h-8 w-8 text-indigo-600" /></div>;
    if (error) return <div className="text-red-500 p-4">{error}</div>;

    return (
        <div className="space-y-6">
            <div className="bg-white p-6 rounded-lg shadow border border-slate-200">
                <h3 className="text-lg font-semibold mb-4">Agent Performance Overview</h3>
                <PerformanceChart
                    data={data}
                    xKey="name"
                    yKeys={['leads_assigned', 'leads_converted']}
                    colors={['#6366f1', '#10b981']}
                />
            </div>

            <div className="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
                <div className="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 className="text-lg font-semibold">Detailed Metrics</h3>
                    <button className="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <Download size={16} /> Export CSV
                    </button>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Agent</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Assigned</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Contacted</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Converted</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Rate</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Score</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-slate-200">
                            {data.map((agent) => (
                                <tr key={agent.user_id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{agent.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{agent.leads_assigned}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{agent.leads_contacted}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{agent.leads_converted}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{agent.conversion_rate}%</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${agent.score >= 80 ? 'bg-green-100 text-green-800' :
                                                agent.score >= 50 ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                            }`}>
                                            {agent.score}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default AgentPerformanceReport;

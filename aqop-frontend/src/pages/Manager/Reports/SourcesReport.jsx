import React, { useState, useEffect } from 'react';
import { getSourceAnalysisReport } from '../../../api/reports';
import DistributionChart from '../../../components/charts/DistributionChart';
import { Loader2 } from 'lucide-react';

const SourcesReport = ({ dateRange }) => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                setLoading(true);
                const response = await getSourceAnalysisReport({
                    from: dateRange.from,
                    to: dateRange.to
                });
                setData(response.data);
            } catch (err) {
                console.error(err);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, [dateRange]);

    if (loading) return <div className="flex justify-center p-8"><Loader2 className="animate-spin h-8 w-8 text-indigo-600" /></div>;

    return (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white p-6 rounded-lg shadow border border-slate-200">
                <h3 className="text-lg font-semibold mb-4">Lead Sources Distribution</h3>
                <DistributionChart data={data} nameKey="source" valueKey="leads_count" />
            </div>

            <div className="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
                <div className="px-6 py-4 border-b border-slate-200">
                    <h3 className="text-lg font-semibold">Source Performance</h3>
                </div>
                <table className="min-w-full divide-y divide-slate-200">
                    <thead className="bg-slate-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Source</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Total Leads</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Converted</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Conv. Rate</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-slate-200">
                        {data.map((item, idx) => (
                            <tr key={idx}>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{item.source}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{item.leads_count}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{item.converted_count}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{item.conversion_rate}%</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default SourcesReport;

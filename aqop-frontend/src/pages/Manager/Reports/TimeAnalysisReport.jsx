import React, { useState, useEffect } from 'react';
import { getTimeAnalysisReport } from '../../../api/reports';
import TrendChart from '../../../components/charts/TrendChart';
import { Loader2 } from 'lucide-react';

const TimeAnalysisReport = ({ dateRange }) => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [period, setPeriod] = useState('daily');

    useEffect(() => {
        const fetchData = async () => {
            try {
                setLoading(true);
                const response = await getTimeAnalysisReport({
                    period,
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
    }, [dateRange, period]);

    if (loading) return <div className="flex justify-center p-8"><Loader2 className="animate-spin h-8 w-8 text-indigo-600" /></div>;

    return (
        <div className="space-y-6">
            <div className="bg-white p-6 rounded-lg shadow border border-slate-200">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="text-lg font-semibold">Lead Volume Trend</h3>
                    <select
                        value={period}
                        onChange={(e) => setPeriod(e.target.value)}
                        className="border-slate-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <TrendChart data={data} xKey="date" yKey="leads" color="#3b82f6" />
            </div>
        </div>
    );
};

export default TimeAnalysisReport;

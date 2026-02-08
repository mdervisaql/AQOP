import React, { useState, useEffect } from 'react';
import { getCampaignPerformanceReport } from '../../../api/reports';
import PerformanceChart from '../../../components/charts/PerformanceChart';
import { Loader2 } from 'lucide-react';

const CampaignsReport = ({ dateRange }) => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                setLoading(true);
                const response = await getCampaignPerformanceReport({
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

    if (data.length === 0) {
        return <div className="text-center p-8 text-slate-500">No campaign data available for this period.</div>;
    }

    return (
        <div className="space-y-6">
            <div className="bg-white p-6 rounded-lg shadow border border-slate-200">
                <h3 className="text-lg font-semibold mb-4">Campaign Performance</h3>
                <PerformanceChart
                    data={data}
                    xKey="campaign"
                    yKeys={['leads_count', 'converted_count']}
                    colors={['#8b5cf6', '#ec4899']}
                />
            </div>
        </div>
    );
};

export default CampaignsReport;

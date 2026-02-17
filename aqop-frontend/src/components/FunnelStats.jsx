/**
 * Funnel Stats Component
 * 
 * Displays detailed funnel statistics with conversion rate calculator.
 */

export default function FunnelStats({ data }) {
  if (!data) return null;

  const { funnel, conversion_rates, targets } = data;

  // Use dynamic targets from data, fallback to defaults
  const defaultTargets = {
    lead_to_response: 30,
    response_to_qualified: 25,
    qualified_to_converted: 40,
    overall: 5,
  };

  const activeTargets = targets || defaultTargets;

  const stats = [
    {
      label: 'الليدات → التواصل',
      labelEn: 'Leads → Response',
      from: funnel.total_leads,
      to: funnel.responded,
      rate: conversion_rates.lead_to_response,
      target: activeTargets.lead_to_response,
      icon: '📱',
    },
    {
      label: 'التواصل → التأهيل',
      labelEn: 'Response → Qualified',
      from: funnel.responded,
      to: funnel.qualified,
      rate: conversion_rates.response_to_qualified,
      target: activeTargets.response_to_qualified,
      icon: '✅',
    },
    {
      label: 'التأهيل → التحويل',
      labelEn: 'Qualified → Converted',
      from: funnel.qualified,
      to: funnel.converted,
      rate: conversion_rates.qualified_to_converted,
      target: activeTargets.qualified_to_converted,
      icon: '💰',
    },
  ];

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-2">
        <h3 className="text-lg font-bold text-gray-900">Conversion Calculator</h3>
        <span className="text-xs text-gray-500">Real-time rates</span>
      </div>

      {/* Conversion Stages */}
      {stats.map((stat, index) => {
        const isLow = stat.rate < stat.target;
        const statusColor = isLow ? 'text-red-600' : 'text-green-600';
        const bgColor = isLow ? 'bg-red-50' : 'bg-green-50';
        const borderColor = isLow ? 'border-red-200' : 'border-green-200';

        return (
          <div key={index} className={`p-4 rounded-lg border ${borderColor} ${bgColor}`}>
            <div className="flex items-start justify-between mb-3">
              <div className="flex items-center gap-2">
                <span className="text-2xl">{stat.icon}</span>
                <div>
                  <div className="font-semibold text-gray-900 text-sm">{stat.label}</div>
                  <div className="text-xs text-gray-500">{stat.labelEn}</div>
                </div>
              </div>
              <div className={`text-2xl font-bold ${statusColor}`}>
                {stat.rate}%
              </div>
            </div>

            {/* Progress Bar */}
            <div className="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden mb-2">
              <div
                className={`absolute top-0 left-0 h-full transition-all duration-500 ${isLow ? 'bg-red-500' : 'bg-green-500'}`}
                style={{ width: `${Math.min(stat.rate, 100)}%` }}
              />
              {/* Target marker */}
              <div
                className="absolute top-0 h-full w-0.5 bg-gray-400"
                style={{ left: `${stat.target}%` }}
              />
            </div>

            {/* Details */}
            <div className="flex items-center justify-between text-xs">
              <div className="text-gray-600">
                <span className="font-medium">{stat.from.toLocaleString()}</span> → <span className="font-medium">{stat.to.toLocaleString()}</span>
              </div>
              <div className="text-gray-500">
                Target: {stat.target}%
              </div>
            </div>

            {/* Warning if below target */}
            {isLow && (
              <div className="mt-2 flex items-start gap-2 text-xs text-red-700">
                <span>⚠️</span>
                <span>أقل من المستهدف بـ {(stat.target - stat.rate).toFixed(1)}%</span>
              </div>
            )}
          </div>
        );
      })}

      {/* Overall Summary */}
      <div className="mt-6 p-4 bg-gradient-to-r from-slate-50 to-slate-100 rounded-lg border border-slate-200">
        <div className="flex items-center justify-between">
          <div>
            <div className="text-sm font-semibold text-gray-900">معدل التحويل النهائي</div>
            <div className="text-xs text-gray-600 mt-0.5">
              من {funnel.total_leads.toLocaleString()} ليد → {funnel.converted.toLocaleString()} محوّل
            </div>
          </div>
          <div className={`text-3xl font-bold ${conversion_rates.overall >= activeTargets.overall ? 'text-green-600' : 'text-amber-600'}`}>
            {conversion_rates.overall}%
          </div>
        </div>
      </div>
    </div>
  );
}

/**
 * Funnel Chart Component
 * 
 * Visual representation of the sales funnel with conversion rates.
 */

export default function FunnelChart({ data }) {
  if (!data) return null;

  const { funnel, conversion_rates, targets } = data;

  // Use dynamic targets from data, fallback to defaults
  const activeTargets = targets || {
    lead_to_response: 30,
    response_to_qualified: 25,
    qualified_to_converted: 40,
    overall: 5,
  };

  const stages = [
    {
      name: 'إجمالي الليدات',
      nameEn: 'Total Leads',
      value: funnel.total_leads,
      color: 'from-blue-500 to-blue-600',
      bgColor: 'bg-blue-50',
      textColor: 'text-blue-700',
      width: 100,
    },
    {
      name: 'تم التواصل',
      nameEn: 'Responded',
      value: funnel.responded,
      color: 'from-indigo-500 to-indigo-600',
      bgColor: 'bg-indigo-50',
      textColor: 'text-indigo-700',
      width: funnel.total_leads > 0 ? (funnel.responded / funnel.total_leads) * 100 : 0,
      rate: conversion_rates.lead_to_response,
    },
    {
      name: 'مؤهل',
      nameEn: 'Qualified',
      value: funnel.qualified,
      color: 'from-purple-500 to-purple-600',
      bgColor: 'bg-purple-50',
      textColor: 'text-purple-700',
      width: funnel.total_leads > 0 ? (funnel.qualified / funnel.total_leads) * 100 : 0,
      rate: conversion_rates.response_to_qualified,
    },
    {
      name: 'محوّل',
      nameEn: 'Converted',
      value: funnel.converted,
      color: 'from-green-500 to-green-600',
      bgColor: 'bg-green-50',
      textColor: 'text-green-700',
      width: funnel.total_leads > 0 ? (funnel.converted / funnel.total_leads) * 100 : 0,
      rate: conversion_rates.qualified_to_converted,
    },
  ];

  return (
    <div className="space-y-3">
      {stages.map((stage, index) => (
        <div key={index} className="relative">
          {/* Stage Bar */}
          <div className="relative">
            <div
              className={`h-16 rounded-lg bg-gradient-to-r ${stage.color} transition-all duration-500 shadow-sm`}
              style={{ width: `${Math.max(stage.width, 15)}%` }}
            >
              <div className="flex items-center justify-between h-full px-4">
                <div>
                  <div className="text-white font-semibold text-sm">{stage.name}</div>
                  <div className="text-white/80 text-xs">{stage.nameEn}</div>
                </div>
                <div className="text-white font-bold text-xl">{stage.value.toLocaleString()}</div>
              </div>
            </div>

            {/* Conversion Rate Badge */}
            {index > 0 && stage.rate !== undefined && (
              <div className="absolute -top-3 right-4 z-10">
                <div className={`${stage.bgColor} ${stage.textColor} px-3 py-1 rounded-full text-xs font-bold shadow-sm border border-white`}>
                  ↓ {stage.rate}%
                </div>
              </div>
            )}
          </div>

          {/* Connecting Arrow */}
          {index < stages.length - 1 && (
            <div className="flex justify-center my-1">
              <svg className="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </div>
          )}
        </div>
      ))}

      {/* Overall Conversion Rate */}
      <div className="mt-6 pt-4 border-t border-gray-200">
        <div className="flex items-center justify-between">
          <div>
            <div className="text-sm text-gray-600">معدل التحويل الإجمالي</div>
            <div className="text-xs text-gray-500">Overall Conversion Rate</div>
          </div>
          <div className={`text-3xl font-bold ${conversion_rates.overall >= activeTargets.overall ? 'text-green-600' : 'text-amber-600'}`}>
            {conversion_rates.overall}%
          </div>
        </div>
        {conversion_rates.overall < activeTargets.overall && (
          <div className="mt-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
            <p className="text-xs text-amber-800">
              ⚠️ معدل التحويل الإجمالي أقل من المستهدف ({activeTargets.overall}%). يُنصح بمراجعة استراتيجية المتابعة.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}

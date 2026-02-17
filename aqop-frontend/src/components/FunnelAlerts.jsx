/**
 * Funnel Alerts Component
 * 
 * Displays alerts when conversion rates drop below thresholds.
 */

import { AlertTriangle, TrendingDown, AlertCircle } from 'lucide-react';

export default function FunnelAlerts({ data }) {
  if (!data || !data.alerts) return null;

  const { alerts, conversion_rates, targets } = data;

  // Use dynamic targets from data, fallback to defaults
  const activeTargets = targets || {
    lead_to_response: 30,
    response_to_qualified: 25,
    qualified_to_converted: 40,
    overall: 5,
  };

  const alertsList = [];

  if (alerts.low_response_rate) {
    alertsList.push({
      level: 'warning',
      icon: AlertTriangle,
      title: 'معدل الاستجابة منخفض',
      titleEn: 'Low Response Rate',
      message: `معدل التواصل الحالي ${conversion_rates.lead_to_response}% (المستهدف: ${activeTargets.lead_to_response}%). يُنصح بتحسين استراتيجية التواصل الأولي.`,
      action: 'تحسين التواصل الأولي',
    });
  }

  if (alerts.low_qualification_rate) {
    alertsList.push({
      level: 'warning',
      icon: TrendingDown,
      title: 'معدل التأهيل منخفض',
      titleEn: 'Low Qualification Rate',
      message: `معدل التأهيل الحالي ${conversion_rates.response_to_qualified}% (المستهدف: ${activeTargets.response_to_qualified}%). قد تحتاج لتحسين جودة الليدات أو معايير التأهيل.`,
      action: 'مراجعة معايير التأهيل',
    });
  }

  if (alerts.low_conversion_rate) {
    alertsList.push({
      level: 'warning',
      icon: AlertCircle,
      title: 'معدل التحويل منخفض',
      titleEn: 'Low Conversion Rate',
      message: `معدل التحويل من المؤهلين ${conversion_rates.qualified_to_converted}% (المستهدف: ${activeTargets.qualified_to_converted}%). يُنصح بتحسين عملية الإغلاق.`,
      action: 'تحسين عملية الإغلاق',
    });
  }

  if (alerts.overall_below_target) {
    alertsList.push({
      level: 'critical',
      icon: AlertTriangle,
      title: 'معدل التحويل الإجمالي منخفض جداً',
      titleEn: 'Critical Overall Conversion',
      message: `معدل التحويل الإجمالي ${conversion_rates.overall}% (المستهدف: ${activeTargets.overall}%+). يتطلب تدخل فوري لمراجعة كامل المسار.`,
      action: 'مراجعة عاجلة',
    });
  }

  if (alertsList.length === 0) {
    return (
      <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
        <div className="flex items-center gap-3">
          <div className="text-green-600 text-2xl">✅</div>
          <div>
            <div className="font-semibold text-green-900 text-sm">أداء جيد</div>
            <div className="text-xs text-green-700 mt-0.5">
              جميع معدلات التحويل ضمن المستهدف أو أعلى
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2 mb-3">
        <AlertTriangle className="w-5 h-5 text-amber-600" />
        <h3 className="font-bold text-gray-900">تنبيهات الأداء</h3>
        <span className="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">
          {alertsList.length}
        </span>
      </div>

      {alertsList.map((alert, index) => {
        const Icon = alert.icon;
        const isCritical = alert.level === 'critical';
        const bgColor = isCritical ? 'bg-red-50' : 'bg-amber-50';
        const borderColor = isCritical ? 'border-red-300' : 'border-amber-300';
        const iconColor = isCritical ? 'text-red-600' : 'text-amber-600';
        const textColor = isCritical ? 'text-red-900' : 'text-amber-900';

        return (
          <div key={index} className={`p-4 ${bgColor} border ${borderColor} rounded-lg`}>
            <div className="flex items-start gap-3">
              <Icon className={`w-5 h-5 ${iconColor} mt-0.5 flex-shrink-0`} />
              <div className="flex-1 min-w-0">
                <div className={`font-semibold ${textColor} text-sm`}>{alert.title}</div>
                <div className="text-xs text-gray-600 mt-0.5">{alert.titleEn}</div>
                <p className="text-sm text-gray-700 mt-2">{alert.message}</p>
                <button className={`mt-3 px-3 py-1.5 ${isCritical ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700'} text-white text-xs font-medium rounded-md transition-colors`}>
                  {alert.action}
                </button>
              </div>
            </div>
          </div>
        );
      })}

      {/* Quick Actions */}
      <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div className="text-xs font-semibold text-blue-900 mb-2">إجراءات سريعة:</div>
        <div className="flex flex-wrap gap-2">
          <button className="px-3 py-1 bg-white border border-blue-300 text-blue-700 text-xs font-medium rounded hover:bg-blue-50 transition-colors">
            📊 عرض التقرير التفصيلي
          </button>
          <button className="px-3 py-1 bg-white border border-blue-300 text-blue-700 text-xs font-medium rounded hover:bg-blue-50 transition-colors">
            👥 مراجعة أداء الفريق
          </button>
          <button className="px-3 py-1 bg-white border border-blue-300 text-blue-700 text-xs font-medium rounded hover:bg-blue-50 transition-colors">
            📈 تحليل الأسباب
          </button>
        </div>
      </div>
    </div>
  );
}

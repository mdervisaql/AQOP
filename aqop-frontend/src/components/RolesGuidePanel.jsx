/**
 * Roles Guide Panel
 *
 * Controlled side drawer that documents what each AQOP role grants, shown on
 * the User Management page. Purely informational — it reads no data and holds
 * no internal open/closed state; visibility is controlled by the parent via
 * the `open` / `onClose` props.
 */

import { Shield, X } from 'lucide-react';

// Scope chip color variants (visibility scope).
const SCOPE_STYLES = {
  blue: 'bg-blue-100 text-blue-700',
  amber: 'bg-amber-100 text-amber-700',
  gray: 'bg-gray-100 text-gray-600',
};

// Static role reference content (highest privilege first).
const ROLES_GUIDE = [
  {
    level: '100',
    nameAr: 'مدير عام',
    nameEn: 'Administrator',
    permissions: 'وصول كامل للنظام و‌AQOP — كل شيء',
    scope: 'كل البيانات',
    scopeColor: 'blue',
  },
  {
    level: '90',
    nameAr: 'مسؤول عمليات',
    nameEn: 'Operation Admin',
    permissions: 'إنشاء/حذف عملاء، إدارة المستخدمين والتكاملات والإعدادات',
    scope: 'كل البيانات',
    scopeColor: 'blue',
  },
  {
    level: '80',
    nameAr: 'مدير عمليات',
    nameEn: 'Operation Manager',
    permissions: 'قراءة وتصدير التحليلات (بدون إنشاء أو حذف)',
    scope: 'كل البيانات',
    scopeColor: 'blue',
  },
  {
    level: '70',
    nameAr: 'مدير دولة',
    nameEn: 'Country Manager',
    permissions: 'يدير ويعيّن عملاء دوله، مع تصدير',
    scope: 'دوله المسنّدة فقط',
    scopeColor: 'amber',
  },
  {
    level: '50',
    nameAr: 'مشرف',
    nameEn: 'Supervisor',
    permissions: 'يدير وكلاء فريقه ويعيّن لهم (بدون إنشاء/حذف)',
    scope: 'القائمة كاملة · إحصائياته فقط',
    scopeColor: 'amber',
  },
  {
    level: '10',
    nameAr: 'وكيل',
    nameEn: 'Agent',
    permissions: 'يعدّل عملاءه، يضيف ملاحظات، يحدّث الحالة',
    scope: 'المسنّدون له فقط',
    scopeColor: 'gray',
  },
  {
    level: '—',
    nameAr: 'تسويق رقمي',
    nameEn: 'Digital Marketing',
    permissions: 'إعلانات فيسبوك والحملات، وعرض العملاء فقط',
    scope: 'عرض فقط',
    scopeColor: 'gray',
  },
];

export default function RolesGuidePanel({ open = false, onClose = () => {} }) {
  return (
    <div dir="rtl">
      {/* Overlay */}
      {open && (
        <div
          className="fixed inset-0 bg-black/50 backdrop-blur-sm z-40"
          onClick={onClose}
        />
      )}

      {/* Drawer */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-96 max-w-[90vw] bg-white shadow-2xl flex flex-col transition-transform duration-300 ${
          open ? 'translate-x-0' : '-translate-x-full'
        }`}
        aria-hidden={!open}
      >
        {/* Header */}
        <div className="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-start justify-between gap-3">
          <div className="flex items-start gap-3">
            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
              <Shield className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-gray-900">دليل صلاحيات الرتب</h2>
              <p className="mt-0.5 text-xs text-gray-500">
                اعرف ما تمنحه كل رتبة قبل تعديل الإسناد
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-2 hover:bg-gray-200 rounded-full text-gray-500 transition"
            title="إغلاق"
          >
            <X size={20} />
          </button>
        </div>

        {/* Roles list */}
        <div className="flex-1 overflow-y-auto p-4 space-y-3">
          {ROLES_GUIDE.map((role) => (
            <div
              key={`${role.level}-${role.nameEn}`}
              className="rounded-xl border border-gray-200 p-4 hover:bg-gray-50 transition"
            >
              <div className="flex items-center gap-3">
                <span className="flex-shrink-0 inline-flex items-center justify-center min-w-[2.5rem] h-10 px-2 rounded-full bg-slate-100 text-slate-700 font-bold text-sm">
                  {role.level}
                </span>
                <div className="min-w-0">
                  <div className="text-sm font-bold text-gray-900">{role.nameAr}</div>
                  <div className="text-xs text-gray-400">{role.nameEn}</div>
                </div>
              </div>

              <p className="mt-3 text-sm text-gray-600 leading-relaxed">
                {role.permissions}
              </p>

              <div className="mt-3">
                <span
                  className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                    SCOPE_STYLES[role.scopeColor]
                  }`}
                >
                  الرؤية: {role.scope}
                </span>
              </div>
            </div>
          ))}
        </div>

        {/* Footer */}
        <div className="px-6 py-4 border-t border-gray-100 bg-gray-50">
          <p className="text-xs text-gray-500 leading-relaxed">
            حقل (الدول المعيّنة) يحدّد نطاق رؤية مدير الدولة تحديدًا.
          </p>
        </div>
      </aside>
    </div>
  );
}

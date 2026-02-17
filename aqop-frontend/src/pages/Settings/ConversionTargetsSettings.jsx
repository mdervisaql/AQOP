import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getTargets, saveTargets } from '../../api/targets';
import { getCountries } from '../../api/leads';
import Navigation from '../../components/Navigation';
import { Save, AlertCircle, CheckCircle, Target } from 'lucide-react';
import { useAuth } from '../../auth/AuthContext';
import { ROLES } from '../../utils/constants';
import { hasAnyRole } from '../../utils/roleHelpers';

function ConversionTargetsSettings() {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const isAdmin = hasAnyRole(user, [ROLES.ADMIN, ROLES.OPERATION_ADMIN]);
  const isCountryManager = hasAnyRole(user, [ROLES.COUNTRY_MANAGER]);

  const [activeTab, setActiveTab] = useState('global'); // 'global' or 'countries'
  const [selectedCountry, setSelectedCountry] = useState('');
  const [formData, setFormData] = useState({
    lead_to_response_target: 30,
    response_to_qualified_target: 25,
    qualified_to_converted_target: 40,
    overall_target: 5,
  });
  const [notification, setNotification] = useState(null);

  // Fetch global targets
  const { data: globalResponse } = useQuery({
    queryKey: ['targets', 'global'],
    queryFn: () => getTargets(null),
    enabled: activeTab === 'global',
  });

  // Fetch countries (for country managers or admins)
  const { data: countriesResponse } = useQuery({
    queryKey: ['countries'],
    queryFn: getCountries,
  });

  // Fetch country-specific targets when a country is selected
  const { data: countryTargetsResponse } = useQuery({
    queryKey: ['targets', 'country', selectedCountry],
    queryFn: () => getTargets(parseInt(selectedCountry)),
    enabled: activeTab === 'countries' && !!selectedCountry,
  });

  // Save targets mutation
  const saveMutation = useMutation({
    mutationFn: saveTargets,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['targets'] });
      queryClient.invalidateQueries({ queryKey: ['funnelStats'] });
      showNotification('تم حفظ المستهدفات بنجاح', 'success');
    },
    onError: (error) => {
      showNotification(error.response?.data?.message || 'حدث خطأ أثناء الحفظ', 'error');
    },
  });

  // Show notification helper
  const showNotification = (message, type) => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 5000);
  };

  // Load targets data when response changes
  useEffect(() => {
    if (activeTab === 'global' && globalResponse?.data) {
      setFormData({
        lead_to_response_target: globalResponse.data.lead_to_response_target || 30,
        response_to_qualified_target: globalResponse.data.response_to_qualified_target || 25,
        qualified_to_converted_target: globalResponse.data.qualified_to_converted_target || 40,
        overall_target: globalResponse.data.overall_target || 5,
      });
    }
  }, [globalResponse, activeTab]);

  useEffect(() => {
    if (activeTab === 'countries' && countryTargetsResponse?.data) {
      setFormData({
        lead_to_response_target: countryTargetsResponse.data.lead_to_response_target || 30,
        response_to_qualified_target: countryTargetsResponse.data.response_to_qualified_target || 25,
        qualified_to_converted_target: countryTargetsResponse.data.qualified_to_converted_target || 40,
        overall_target: countryTargetsResponse.data.overall_target || 5,
      });
    }
  }, [countryTargetsResponse, activeTab]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: parseFloat(value) || 0,
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const data = {
      ...formData,
      country_id: activeTab === 'countries' && selectedCountry ? parseInt(selectedCountry) : null,
    };
    saveMutation.mutate(data);
  };

  // Filter countries based on user role
  const getAvailableCountries = () => {
    if (!countriesResponse?.data) return [];
    const allCountries = countriesResponse.data;
    
    if (isAdmin) {
      return allCountries;
    }
    
    if (isCountryManager && user?.country_ids) {
      return allCountries.filter((c) => user.country_ids.includes(parseInt(c.id)));
    }
    
    return [];
  };

  const availableCountries = getAvailableCountries();

  return (
    <div className="flex min-h-screen bg-gray-50 rtl">
      <Navigation />
      <main className="flex-1 lg:mr-64">
        <div className="max-w-4xl mx-auto px-4 py-8">
          {/* Page Header */}
          <div className="mb-6">
            <div className="flex items-center gap-3 mb-2">
              <Target className="w-8 h-8 text-teal-600" />
              <h1 className="text-3xl font-bold text-gray-800">إدارة المستهدفات</h1>
            </div>
            <p className="text-gray-600">
              حدد معدلات التحويل المستهدفة للقمع البيعي (Funnel Targets)
            </p>
          </div>

          {/* Notification */}
          {notification && (
            <div
              className={`mb-6 p-4 rounded-lg flex items-center gap-3 ${
                notification.type === 'success'
                  ? 'bg-green-50 text-green-800 border border-green-200'
                  : 'bg-red-50 text-red-800 border border-red-200'
              }`}
            >
              {notification.type === 'success' ? (
                <CheckCircle className="w-5 h-5" />
              ) : (
                <AlertCircle className="w-5 h-5" />
              )}
              <p>{notification.message}</p>
            </div>
          )}

          {/* Tabs */}
          <div className="mb-6 border-b border-gray-200">
            <nav className="flex gap-4">
              {isAdmin && (
                <button
                  onClick={() => setActiveTab('global')}
                  className={`pb-3 px-2 text-sm font-medium transition-colors border-b-2 ${
                    activeTab === 'global'
                      ? 'border-teal-600 text-teal-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  المستهدفات العامة
                </button>
              )}
              <button
                onClick={() => setActiveTab('countries')}
                className={`pb-3 px-2 text-sm font-medium transition-colors border-b-2 ${
                  activeTab === 'countries'
                    ? 'border-teal-600 text-teal-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                مستهدفات الدول
              </button>
            </nav>
          </div>

          {/* Content */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            {activeTab === 'countries' && (
              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  اختر الدولة
                </label>
                <select
                  value={selectedCountry}
                  onChange={(e) => setSelectedCountry(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                >
                  <option value="">-- اختر دولة --</option>
                  {availableCountries.map((country) => (
                    <option key={country.id} value={country.id}>
                      {country.country_name_ar || country.country_name_en}
                    </option>
                  ))}
                </select>
                {countryTargetsResponse?.data?.is_global_fallback && (
                  <p className="mt-2 text-sm text-amber-600">
                    ℹ️ هذه الدولة تستخدم المستهدفات العامة حالياً. يمكنك تخصيص مستهدفات خاصة بها.
                  </p>
                )}
              </div>
            )}

            {(activeTab === 'global' || selectedCountry) && (
              <form onSubmit={handleSubmit}>
                <div className="space-y-6">
                  {/* Lead to Response Target */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      مستهدف: Lead → Response (%)
                    </label>
                    <input
                      type="number"
                      name="lead_to_response_target"
                      value={formData.lead_to_response_target}
                      onChange={handleChange}
                      min="0"
                      max="100"
                      step="0.1"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                      required
                    />
                    <p className="mt-1 text-xs text-gray-500">
                      النسبة المستهدفة لتحويل Lead إلى Response (رد/تواصل)
                    </p>
                  </div>

                  {/* Response to Qualified Target */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      مستهدف: Response → Qualified (%)
                    </label>
                    <input
                      type="number"
                      name="response_to_qualified_target"
                      value={formData.response_to_qualified_target}
                      onChange={handleChange}
                      min="0"
                      max="100"
                      step="0.1"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                      required
                    />
                    <p className="mt-1 text-xs text-gray-500">
                      النسبة المستهدفة لتحويل Response إلى Qualified (مؤهل)
                    </p>
                  </div>

                  {/* Qualified to Converted Target */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      مستهدف: Qualified → Converted (%)
                    </label>
                    <input
                      type="number"
                      name="qualified_to_converted_target"
                      value={formData.qualified_to_converted_target}
                      onChange={handleChange}
                      min="0"
                      max="100"
                      step="0.1"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                      required
                    />
                    <p className="mt-1 text-xs text-gray-500">
                      النسبة المستهدفة لتحويل Qualified إلى Converted (محول/مدفوع)
                    </p>
                  </div>

                  {/* Overall Target */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      المستهدف الإجمالي: Lead → Converted (%)
                    </label>
                    <input
                      type="number"
                      name="overall_target"
                      value={formData.overall_target}
                      onChange={handleChange}
                      min="0"
                      max="100"
                      step="0.1"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                      required
                    />
                    <p className="mt-1 text-xs text-gray-500">
                      النسبة المستهدفة الإجمالية من Lead إلى Converted مباشرة
                    </p>
                  </div>

                  {/* Submit Button */}
                  <button
                    type="submit"
                    disabled={saveMutation.isPending}
                    className="w-full flex items-center justify-center gap-2 bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Save className="w-5 h-5" />
                    {saveMutation.isPending ? 'جاري الحفظ...' : 'حفظ المستهدفات'}
                  </button>
                </div>
              </form>
            )}

            {activeTab === 'countries' && !selectedCountry && (
              <div className="text-center py-12 text-gray-500">
                <Target className="w-16 h-16 mx-auto mb-3 text-gray-300" />
                <p>اختر دولة لعرض وتعديل مستهدفاتها</p>
              </div>
            )}
          </div>

          {/* Info Box */}
          <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 className="font-semibold text-blue-900 mb-2">ملاحظات:</h3>
            <ul className="text-sm text-blue-800 space-y-1 list-disc list-inside">
              {isAdmin && (
                <li>المستهدفات العامة تُطبق على جميع الدول التي ليس لها مستهدفات مخصصة</li>
              )}
              <li>مستهدفات الدول لها الأولوية على المستهدفات العامة</li>
              <li>يتم استخدام هذه المستهدفات في التنبيهات (Alerts) على لوحة التحكم</li>
            </ul>
          </div>
        </div>
      </main>
    </div>
  );
}

export default ConversionTargetsSettings;

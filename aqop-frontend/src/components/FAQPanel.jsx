/**
 * FAQ Panel Component
 * 
 * Shows pre-approved questions & answers for agents during communication.
 * Filterable by country (auto-detected from lead) and searchable.
 * Click to copy answer to clipboard for easy pasting.
 */

import { useState, useEffect } from 'react';
import { getFaqs } from '../api/faq';

export default function FAQPanel({ countryId }) {
  const [faqs, setFaqs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [expandedId, setExpandedId] = useState(null);
  const [copiedId, setCopiedId] = useState(null);

  useEffect(() => {
    loadFaqs();
  }, [countryId]);

  const loadFaqs = async () => {
    setLoading(true);
    try {
      const params = {};
      if (countryId) params.country_id = countryId;
      const res = await getFaqs(params);
      if (res?.data) setFaqs(res.data);
    } catch (err) {
      console.error('Error loading FAQs:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleCopy = async (answer, faqId) => {
    try {
      await navigator.clipboard.writeText(answer);
      setCopiedId(faqId);
      setTimeout(() => setCopiedId(null), 2000);
    } catch (err) {
      // Fallback for older browsers
      const textarea = document.createElement('textarea');
      textarea.value = answer;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      setCopiedId(faqId);
      setTimeout(() => setCopiedId(null), 2000);
    }
  };

  const filteredFaqs = searchTerm
    ? faqs.filter(f =>
      f.question.toLowerCase().includes(searchTerm.toLowerCase()) ||
      f.answer.toLowerCase().includes(searchTerm.toLowerCase())
    )
    : faqs;

  // Group by category
  const grouped = {};
  filteredFaqs.forEach(faq => {
    const cat = faq.category || 'عام';
    if (!grouped[cat]) grouped[cat] = [];
    grouped[cat].push(faq);
  });

  if (loading) {
    return (
      <div className="flex justify-center items-center py-8">
        <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-amber-500"></div>
      </div>
    );
  }

  if (faqs.length === 0) {
    return (
      <div className="text-center py-8 text-gray-400">
        <svg className="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p className="text-sm">لا توجد أسئلة وأجوبة لهذه الدولة</p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {/* Search */}
      <div className="relative">
        <svg className="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          placeholder="ابحث في الأسئلة..."
          className="w-full pr-9 pl-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-gray-50"
          dir="rtl"
        />
      </div>

      {/* FAQ Items */}
      <div className="max-h-[500px] overflow-y-auto space-y-2 pr-1">
        {Object.entries(grouped).map(([category, items]) => (
          <div key={category}>
            {Object.keys(grouped).length > 1 && (
              <div className="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5 mt-3 first:mt-0">
                {category}
              </div>
            )}
            {items.map(faq => (
              <div key={faq.id} className="border border-gray-100 rounded-lg overflow-hidden bg-white hover:border-amber-200 transition-colors">
                {/* Question - clickable */}
                <button
                  onClick={() => setExpandedId(expandedId === faq.id ? null : faq.id)}
                  className="w-full text-right px-3 py-2.5 flex items-start gap-2 hover:bg-amber-50/50 transition-colors"
                  dir="auto"
                >
                  <svg className={`w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0 transition-transform ${expandedId === faq.id ? 'rotate-90' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                  <span className="text-sm font-medium text-gray-800 text-right flex-1">{faq.question}</span>
                </button>

                {/* Answer - expanded */}
                {expandedId === faq.id && (
                  <div className="px-3 pb-3 border-t border-gray-50">
                    <div className="bg-green-50 rounded-lg p-3 mt-2 relative group">
                      <p className="text-sm text-gray-700 whitespace-pre-wrap pr-8" dir="auto">{faq.answer}</p>
                      <button
                        onClick={() => handleCopy(faq.answer, faq.id)}
                        className="absolute top-2 left-2 p-1.5 rounded-md bg-white/80 hover:bg-white shadow-sm text-gray-500 hover:text-green-600 transition-all opacity-0 group-hover:opacity-100"
                        title="نسخ الإجابة"
                      >
                        {copiedId === faq.id ? (
                          <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                        ) : (
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                          </svg>
                        )}
                      </button>
                    </div>
                    {copiedId === faq.id && (
                      <p className="text-[11px] text-green-600 mt-1 text-center">تم نسخ الإجابة!</p>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        ))}
      </div>

      <p className="text-[11px] text-gray-400 text-center pt-1">
        {filteredFaqs.length} سؤال متاح - اضغط على السؤال ثم انسخ الإجابة
      </p>
    </div>
  );
}

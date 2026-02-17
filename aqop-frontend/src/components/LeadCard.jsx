/**
 * Lead Card Component
 * 
 * Mobile-first card component for displaying lead information.
 * Features large touch targets (min 48px) and quick action buttons.
 */

import { Link } from 'react-router-dom';
import { Mail, Phone, MessageCircle, ChevronRight } from 'lucide-react';
import { getStatusColor, getPriorityColor } from '../api/leads';
import { formatDateTime } from '../utils/helpers';
import LeadScore from './LeadScore';

export default function LeadCard({ lead, showAssignee = false, onClick }) {
  // Format phone for WhatsApp link
  const whatsappNumber = (lead.phone || lead.whatsapp || '').replace(/\D/g, '');

  return (
    <div
      onClick={onClick}
      className={`
        bg-white rounded-xl shadow-sm border border-gray-100 p-4
        active:bg-gray-50 active:scale-[0.99]
        transition-all duration-150
        ${onClick ? 'cursor-pointer touch-manipulation' : ''}
      `}
    >
      {/* Header: Name, ID, Status */}
      <div className="flex items-start justify-between mb-3">
        <div className="flex-1 min-w-0">
          <Link
            to={`/leads/${lead.id}`}
            onClick={(e) => e.stopPropagation()}
            className="text-lg font-bold text-gray-900 hover:text-blue-600 truncate block max-w-[200px] sm:max-w-[300px] lg:max-w-none"
          >
            {lead.name || 'بدون اسم'}
          </Link>
          <p className="text-sm text-gray-500">#{lead.id}</p>
        </div>
        <span className={`
          px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap ml-2
          ${getStatusColor(lead.status_code)}
        `}>
          {lead.status_name_en || lead.status_code}
        </span>
      </div>

      {/* Lead Score */}
      {lead.lead_score !== undefined && lead.lead_score !== null && (
        <div className="mb-3">
          <LeadScore score={lead.lead_score} rating={lead.lead_rating} size="sm" />
        </div>
      )}

      {/* Badges Row */}
      <div className="flex flex-wrap gap-2 mb-3">
        {/* Priority Badge */}
        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${getPriorityColor(lead.priority)}`}>
          {lead.priority}
        </span>

        {/* Country */}
        {lead.country_name_en && (
          <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
            {lead.country_name_en}
          </span>
        )}

        {/* Source */}
        {lead.source_name && (
          <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
            {lead.source_name}
          </span>
        )}

        {/* Campaign */}
        {lead.campaign_name && (
          <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
            {lead.campaign_name}
          </span>
        )}
      </div>

      {/* Assignee */}
      {showAssignee && lead.assigned_to_name && (
        <div className="text-sm text-gray-600 mb-3">
          <span className="font-medium">Assigned to:</span> {lead.assigned_to_name}
        </div>
      )}

      {/* Notes Preview */}
      {lead.notes && (
        <p className="text-sm text-gray-600 line-clamp-2 mb-3">
          {lead.notes}
        </p>
      )}

      {/* Timestamps */}
      <div className="flex items-center gap-4 text-xs text-gray-500 mb-4">
        <span>Created: {formatDateTime(lead.created_at)}</span>
        {lead.last_contact_at && (
          <span>Last Contact: {formatDateTime(lead.last_contact_at)}</span>
        )}
      </div>

      {/* Quick Actions - Large Touch Targets (48px min height) */}
      <div className="flex gap-2 pt-3 border-t border-gray-100">
        {/* Call Button */}
        {lead.phone && (
          <a
            href={`tel:${lead.phone}`}
            onClick={(e) => e.stopPropagation()}
            className="
              flex-1 flex items-center justify-center gap-2
              bg-green-50 text-green-700 hover:bg-green-100
              py-3 rounded-lg font-medium text-sm
              active:bg-green-200 active:scale-95
              transition-all duration-150
              min-h-[48px] touch-manipulation
            "
          >
            <Phone className="w-5 h-5" />
            <span className="hidden sm:inline">اتصال</span>
          </a>
        )}

        {/* WhatsApp Button */}
        {whatsappNumber && (
          <a
            href={`https://wa.me/${whatsappNumber}`}
            onClick={(e) => e.stopPropagation()}
            target="_blank"
            rel="noopener noreferrer"
            className="
              flex-1 flex items-center justify-center gap-2
              bg-emerald-50 text-emerald-700 hover:bg-emerald-100
              py-3 rounded-lg font-medium text-sm
              active:bg-emerald-200 active:scale-95
              transition-all duration-150
              min-h-[48px] touch-manipulation
            "
          >
            <MessageCircle className="w-5 h-5" />
            <span className="hidden sm:inline">واتساب</span>
          </a>
        )}

        {/* Email Button */}
        {lead.email && (
          <a
            href={`mailto:${lead.email}`}
            onClick={(e) => e.stopPropagation()}
            className="
              flex-1 flex items-center justify-center gap-2
              bg-blue-50 text-blue-700 hover:bg-blue-100
              py-3 rounded-lg font-medium text-sm
              active:bg-blue-200 active:scale-95
              transition-all duration-150
              min-h-[48px] touch-manipulation
            "
          >
            <Mail className="w-5 h-5" />
            <span className="hidden sm:inline">بريد</span>
          </a>
        )}

        {/* View Details */}
        <Link
          to={`/leads/${lead.id}`}
          onClick={(e) => e.stopPropagation()}
          className="
            flex items-center justify-center
            bg-gray-50 text-gray-700 hover:bg-gray-100
            px-4 py-3 rounded-lg font-medium text-sm
            active:bg-gray-200 active:scale-95
            transition-all duration-150
            min-h-[48px] touch-manipulation
          "
        >
          <ChevronRight className="w-5 h-5" />
        </Link>
      </div>
    </div>
  );
}

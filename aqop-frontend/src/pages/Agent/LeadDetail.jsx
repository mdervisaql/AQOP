/**
 * Lead Detail Page
 * 
 * Detailed view of a single lead with notes and status management.
 */

import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { getLead, updateLeadStatus, addLeadNote, getLeadNotes, getLeadEvents, uploadLeadFile, recalculateLeadScore, getLeadScoreHistory } from '../../api/leads';
import { getStatusColor, getPriorityColor } from '../../api/leads';
import { formatDateTime } from '../../utils/helpers';
import { useAuth } from '../../auth/AuthContext';
import Navigation from '../../components/Navigation';
import LoadingSpinner from '../../components/LoadingSpinner';

import CommunicationLog from '../../components/CommunicationLog';
import WhatsAppChat from '../../components/WhatsAppChat';
import LeadScore from '../../components/LeadScore';

export default function LeadDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [lead, setLead] = useState(null);
  const [notes, setNotes] = useState([]);
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('details'); // details, communications, whatsapp

  const [newNote, setNewNote] = useState('');
  const [addingNote, setAddingNote] = useState(false);

  const [selectedStatus, setSelectedStatus] = useState('');
  const [updatingStatus, setUpdatingStatus] = useState(false);
  const [uploadingFile, setUploadingFile] = useState(false);

  const [scoreHistory, setScoreHistory] = useState([]);
  const [recalculatingScore, setRecalculatingScore] = useState(false);

  useEffect(() => {
    fetchLeadData();
  }, [id]);

  const fetchLeadData = async () => {
    setLoading(true);
    setError(null);

    try {
      // Fetch lead details
      const leadResponse = await getLead(id);

      if (leadResponse.success && leadResponse.data) {
        setLead(leadResponse.data);
        setSelectedStatus(leadResponse.data.status_code);

        // Fetch notes
        try {
          const notesResponse = await getLeadNotes(id);
          if (notesResponse.success && notesResponse.data) {
            setNotes(notesResponse.data || []);
          }
        } catch (err) {
          console.error('Error fetching notes:', err);
        }

        // Fetch events
        try {
          const eventsResponse = await getLeadEvents(id);
          if (eventsResponse.success && eventsResponse.data) {
            setEvents(eventsResponse.data || []);
          }
        } catch (err) {
          console.error('Error fetching events:', err);
        }

        // Fetch score history
        try {
          const historyResponse = await getLeadScoreHistory(id);
          if (historyResponse.success && historyResponse.data) {
            setScoreHistory(historyResponse.data || []);
          }
        } catch (err) {
          console.error('Error fetching score history:', err);
        }
      } else {
        setError('Lead not found');
      }
    } catch (err) {
      console.error('Error fetching lead:', err);
      setError(err.message || 'Failed to load lead');
    } finally {
      setLoading(false);
    }
  };

  const handleAddNote = async (e) => {
    e.preventDefault();

    if (!newNote.trim()) return;

    setAddingNote(true);

    try {
      await addLeadNote(id, newNote);
      setNewNote('');

      // Refresh notes
      const notesResponse = await getLeadNotes(id);
      if (notesResponse.success && notesResponse.data) {
        setNotes(notesResponse.data || []);
      }
    } catch (err) {
      console.error('Error adding note:', err);
      alert('Failed to add note. Please try again.');
    } finally {
      setAddingNote(false);
    }
  };

  const handleStatusChange = async () => {
    if (!selectedStatus || selectedStatus === lead.status_code) return;

    setUpdatingStatus(true);

    try {
      await updateLeadStatus(id, selectedStatus);

      // Refresh lead data
      const leadResponse = await getLead(id);
      if (leadResponse.success && leadResponse.data) {
        setLead(leadResponse.data);
      }

      alert('Status updated successfully!');
    } catch (err) {
      console.error('Error updating status:', err);
      alert('Failed to update status. Please try again.');
      setSelectedStatus(lead.status_code);
    } finally {
      setUpdatingStatus(false);
    }
  };

  const handleFileUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setUploadingFile(true);

    try {
      await uploadLeadFile(id, file);
      alert('Screenshot uploaded successfully!');

      // Refresh events to show the upload log
      const eventsResponse = await getLeadEvents(id);
      if (eventsResponse.success && eventsResponse.data) {
        setEvents(eventsResponse.data || []);
      }
    } catch (err) {
      console.error('Error uploading file:', err);
      alert('Failed to upload file. Please try again.');
    } finally {
      setUploadingFile(false);
      // Reset file input
      e.target.value = null;
    }
  };

  const handleRecalculateScore = async () => {
    if (recalculatingScore) return;

    setRecalculatingScore(true);

    try {
      const response = await recalculateLeadScore(id);

      if (response.success && response.data) {
        // Update lead with new score
        setLead(prev => ({
          ...prev,
          lead_score: response.data.score,
          lead_rating: response.data.rating,
          score_updated_at: response.data.updated_at
        }));

        // Refresh history
        const historyResponse = await getLeadScoreHistory(id);
        if (historyResponse.success && historyResponse.data) {
          setScoreHistory(historyResponse.data || []);
        }

        alert('Score recalculated successfully!');
      }
    } catch (err) {
      console.error('Error recalculating score:', err);
      alert('Failed to recalculate score. Please try again.');
    } finally {
      setRecalculatingScore(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <LoadingSpinner size="lg" text="Loading lead details..." />
      </div>
    );
  }

  if (error || !lead) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
          <p className="text-red-800">{error || 'Lead not found'}</p>
          <button
            onClick={() => navigate('/leads')}
            className="mt-4 text-sm text-red-600 hover:text-red-800 underline"
          >
            ← Back to Leads
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Desktop Sidebar - Hidden on mobile */}
      <div className="hidden lg:block lg:fixed lg:inset-y-0 lg:w-64">
        <Navigation currentPage="lead-detail" />
      </div>

      {/* Mobile Navigation (Header + Drawer) */}
      <div className="lg:hidden">
        <Navigation currentPage="lead-detail" />
      </div>

      {/* Main Content */}
      <main className="lg:ml-64 pt-14 lg:pt-0 pb-24 lg:pb-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 lg:py-8">
          {/* Breadcrumb - Hidden on mobile */}
          <nav className="mb-4 lg:mb-6 hidden lg:block">
            <Link to="/leads" className="text-blue-600 hover:text-blue-800 text-sm">
              ← Back to My Leads
            </Link>
          </nav>

          {/* Mobile Back Button */}
          <div className="lg:hidden mb-4">
            <Link to="/leads" className="inline-flex items-center text-blue-600 text-sm">
              <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
              رجوع
            </Link>
          </div>

          {/* Header - Compact for Mobile */}
          <div className="bg-white rounded-xl shadow-sm p-4 lg:p-6 mb-4 lg:mb-6">
            {/* Top: Name + Badges */}
            <div className="flex items-start justify-between gap-3">
              <div className="flex-1 min-w-0">
                <h1 className="text-xl lg:text-3xl font-bold text-gray-900 line-clamp-2 leading-tight">
                  {lead.name}
                </h1>
                <p className="text-sm text-gray-500 mt-1">Lead ID: #{lead.id}</p>
              </div>
              <div className="flex flex-col gap-1.5 shrink-0">
                <span className={`px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap ${getStatusColor(lead.status_code)}`}>
                  {lead.status_name_en}
                </span>
                <span className={`px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap ${getPriorityColor(lead.priority)}`}>
                  {lead.priority}
                </span>
              </div>
            </div>

            {/* Quick Actions - Mobile Prominent, Desktop in Sidebar */}
            <div className="flex gap-2 mt-4 lg:hidden">
              {lead.phone && (
                <a
                  href={`tel:${lead.phone}`}
                  className="flex-1 flex items-center justify-center gap-2 bg-green-500 text-white py-3 rounded-xl font-medium active:bg-green-600 transition-colors min-h-[48px] touch-manipulation"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  اتصال
                </a>
              )}

              {(lead.whatsapp || lead.phone) && (
                <a
                  href={`https://wa.me/${(lead.whatsapp || lead.phone)?.replace(/\D/g, '')}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex-1 flex items-center justify-center gap-2 bg-emerald-500 text-white py-3 rounded-xl font-medium active:bg-emerald-600 transition-colors min-h-[48px] touch-manipulation"
                >
                  <svg className="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                  </svg>
                  واتساب
                </a>
              )}
            </div>

            {/* Tabs - Scrollable on Mobile */}
            <div className="mt-4 lg:mt-8 -mx-4 lg:mx-0 border-b border-gray-200 overflow-x-auto scrollbar-hide">
              <div className="flex min-w-max px-4 lg:px-0 lg:space-x-8">
                <button
                  onClick={() => setActiveTab('details')}
                  className={`px-4 lg:px-0 py-3 lg:pb-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors ${activeTab === 'details'
                    ? 'text-blue-600 border-blue-600'
                    : 'text-gray-500 border-transparent hover:text-gray-700'
                    }`}
                >
                  التفاصيل
                </button>
                <button
                  onClick={() => setActiveTab('communications')}
                  className={`px-4 lg:px-0 py-3 lg:pb-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors ${activeTab === 'communications'
                    ? 'text-blue-600 border-blue-600'
                    : 'text-gray-500 border-transparent hover:text-gray-700'
                    }`}
                >
                  سجل التواصل
                </button>
                <button
                  onClick={() => setActiveTab('whatsapp')}
                  className={`px-4 lg:px-0 py-3 lg:pb-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors ${activeTab === 'whatsapp'
                    ? 'text-green-600 border-green-600'
                    : 'text-gray-500 border-transparent hover:text-gray-700'
                    }`}
                >
                  واتساب
                </button>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            {/* Main Content */}
            <div className="lg:col-span-2 space-y-4 lg:space-y-6">

              {activeTab === 'communications' ? (
                <div className="bg-white rounded-xl shadow-sm p-4 lg:p-6">
                  <CommunicationLog leadId={id} />
                </div>
              ) : activeTab === 'whatsapp' ? (
                <div className="bg-white rounded-xl shadow-sm p-4 lg:p-6">
                  <WhatsAppChat leadId={id} leadPhone={lead.whatsapp || lead.phone} />
                </div>
              ) : (
                <>
                  {/* Contact Information */}
                  <div className="bg-white rounded-lg shadow-md p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Contact Information</h2>
                    <dl className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {lead.email && (
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Email</dt>
                          <dd className="mt-1">
                            <a href={`mailto:${lead.email}`} className="text-blue-600 hover:text-blue-800">
                              {lead.email}
                            </a>
                          </dd>
                        </div>
                      )}
                      {lead.phone && (
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Phone</dt>
                          <dd className="mt-1">
                            <a href={`tel:${lead.phone}`} className="text-blue-600 hover:text-blue-800">
                              {lead.phone}
                            </a>
                          </dd>
                        </div>
                      )}
                      {lead.whatsapp && (
                        <div>
                          <dt className="text-sm font-medium text-gray-500">WhatsApp</dt>
                          <dd className="mt-1">
                            <a
                              href={`https://wa.me/${lead.whatsapp}`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="text-blue-600 hover:text-blue-800 flex items-center gap-1"
                            >
                              {lead.whatsapp}
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                              </svg>
                            </a>
                          </dd>
                        </div>
                      )}
                      {lead.country_name_en && (
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Country</dt>
                          <dd className="mt-1 text-gray-900">{lead.country_name_en}</dd>
                        </div>
                      )}
                    </dl>
                  </div>

                  {/* Lead Details */}
                  <div className="bg-white rounded-lg shadow-md p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Lead Details</h2>
                    <dl className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Source</dt>
                        <dd className="mt-1 text-gray-900">{lead.source_name || 'N/A'}</dd>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Campaign</dt>
                        <dd className="mt-1 text-gray-900">{lead.campaign_name || 'N/A'}</dd>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Assigned To</dt>
                        <dd className="mt-1 text-gray-900">{lead.assigned_to_name || 'Unassigned'}</dd>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Created</dt>
                        <dd className="mt-1 text-gray-900">{formatDateTime(lead.created_at)}</dd>
                      </div>
                      {lead.last_contact_at && (
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Last Contact</dt>
                          <dd className="mt-1 text-gray-900">{formatDateTime(lead.last_contact_at)}</dd>
                        </div>
                      )}
                      {lead.updated_at && (
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Last Updated</dt>
                          <dd className="mt-1 text-gray-900">{formatDateTime(lead.updated_at)}</dd>
                        </div>
                      )}
                    </dl>

                    {lead.notes && (
                      <div className="mt-4 pt-4 border-t border-gray-200">
                        <dt className="text-sm font-medium text-gray-500 mb-2">Initial Notes</dt>
                        <dd className="text-gray-900 whitespace-pre-wrap">{lead.notes}</dd>
                      </div>
                    )}
                  </div>

                  {/* Campaign Questions */}
                  {lead.custom_fields && (() => {
                    try {
                      const customFields = typeof lead.custom_fields === 'string'
                        ? JSON.parse(lead.custom_fields)
                        : lead.custom_fields;

                      if (!customFields || Object.keys(customFields).length === 0) {
                        return null;
                      }

                      return (
                        <div className="bg-white rounded-lg shadow-md p-6">
                          <h2 className="text-xl font-semibold text-gray-900 mb-4">Campaign Questions</h2>
                          <div className="space-y-4">
                            {Object.entries(customFields).map(([key, value]) => {
                              // Format 1: New format with question/answer objects
                              if (value && typeof value === 'object' && value.question && value.answer) {
                                return (
                                  <div key={key} className="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                                    <dt className="text-sm font-semibold text-gray-700 mb-1" dir="auto">
                                      {value.question}
                                    </dt>
                                    <dd className="text-gray-900" dir="auto">
                                      {value.answer}
                                    </dd>
                                  </div>
                                );
                              }

                              // Format 2: Old format - simple key-value pairs
                              return (
                                <div key={key} className="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                                  <dt className="text-sm font-semibold text-gray-700 mb-1" dir="auto">
                                    {key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                  </dt>
                                  <dd className="text-gray-900" dir="auto">
                                    {typeof value === 'object' ? JSON.stringify(value) : String(value)}
                                  </dd>
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      );
                    } catch (error) {
                      console.error('Error parsing custom_fields:', error);
                      return null;
                    }
                  })()}

                  {/* Notes Section */}
                  <div className="bg-white rounded-lg shadow-md p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Notes & Activity</h2>

                    {/* Add Note Form */}
                    <form onSubmit={handleAddNote} className="mb-6">
                      <textarea
                        value={newNote}
                        onChange={(e) => setNewNote(e.target.value)}
                        placeholder="Add a note about this lead..."
                        rows={3}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        disabled={addingNote}
                      />
                      <div className="mt-2 flex justify-end">
                        <button
                          type="submit"
                          disabled={addingNote || !newNote.trim()}
                          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          {addingNote ? 'Adding...' : 'Add Note'}
                        </button>
                      </div>
                    </form>

                    {/* Notes List */}
                    <div className="space-y-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                      {notes.length === 0 ? (
                        <div className="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                          <p className="text-gray-500">No notes yet. Start the conversation!</p>
                        </div>
                      ) : (
                        notes.map((note) => {
                          const isCurrentUser = note.user_id === user?.id; // Assuming note has user_id, otherwise rely on name check
                          return (
                            <div key={note.id} className={`flex gap-3 ${isCurrentUser ? 'flex-row-reverse' : ''}`}>
                              <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium ${isCurrentUser ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'}`}>
                                {note.user_name ? note.user_name.charAt(0).toUpperCase() : 'U'}
                              </div>
                              <div className={`flex flex-col max-w-[80%] ${isCurrentUser ? 'items-end' : 'items-start'}`}>
                                <div className={`rounded-lg p-3 ${isCurrentUser ? 'bg-blue-50 text-blue-900 rounded-tr-none' : 'bg-gray-50 text-gray-900 rounded-tl-none'}`}>
                                  <p className="whitespace-pre-wrap text-sm">{note.note_text}</p>
                                </div>
                                <span className="text-xs text-gray-400 mt-1">
                                  {note.user_name || user?.display_name} • {formatDateTime(note.created_at)}
                                </span>
                              </div>
                            </div>
                          );
                        })
                      )}
                    </div>
                  </div>


                  {/* Activity Log Section */}
                  <div className="bg-white rounded-lg shadow-md p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Activity Log</h2>

                    <div className="space-y-3">
                      {events.length === 0 ? (
                        <p className="text-gray-500 text-center py-4">No activity yet</p>
                      ) : (
                        events.map((event) => (
                          <div key={event.id} className="flex gap-3 border-l-2 border-blue-200 pl-4 py-2">
                            <div className="flex-1">
                              <div className="flex items-center gap-2 mb-1">
                                <span className="font-medium text-gray-900">{event.user_name || 'System'}</span>
                                <span className="text-xs text-gray-500">{formatDateTime(event.created_at)}</span>
                              </div>
                              <p className="text-sm text-gray-700">{event.event_name || event.event_type}</p>
                              {event.payload && Object.keys(event.payload).length > 0 && (
                                <div className="mt-1 text-xs text-gray-600">
                                  {event.payload.old_status && event.payload.new_status && (
                                    <span>Status: {event.payload.old_status} → {event.payload.new_status}</span>
                                  )}
                                  {event.payload.assigned_to && (
                                    <span>Assigned to: {event.payload.assigned_to}</span>
                                  )}
                                  {event.event_name === 'file_uploaded' && event.payload.file_name && (
                                    <div className="flex items-center gap-2 mt-1">
                                      <span className="text-gray-600">Uploaded:</span>
                                      <span className="font-medium text-blue-600">{event.payload.file_name}</span>
                                      {event.payload.dropbox_path && (
                                        <span className="text-xs text-gray-400">({event.payload.dropbox_path})</span>
                                      )}
                                    </div>
                                  )}
                                </div>
                              )}
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </div>
                </>
              )}
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Lead Score */}
              <div className="bg-white rounded-lg shadow-md p-6">
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-lg font-semibold text-gray-900">Lead Score</h3>
                  <button
                    onClick={handleRecalculateScore}
                    disabled={recalculatingScore}
                    className="text-xs text-blue-600 hover:text-blue-800 disabled:opacity-50"
                  >
                    {recalculatingScore ? 'Calculating...' : 'Recalculate'}
                  </button>
                </div>

                <div className="flex justify-center mb-4">
                  <LeadScore score={lead.lead_score} rating={lead.lead_rating} size="lg" showLabel={true} />
                </div>

                {lead.score_updated_at && (
                  <p className="text-xs text-center text-gray-500 mb-4">
                    Last updated: {formatDateTime(lead.score_updated_at)}
                  </p>
                )}

                {scoreHistory.length > 0 && (
                  <div className="mt-4 pt-4 border-t border-gray-200">
                    <h4 className="text-sm font-medium text-gray-700 mb-2">Score History</h4>
                    <div className="space-y-2 max-h-40 overflow-y-auto custom-scrollbar">
                      {scoreHistory.map((history, index) => (
                        <div key={index} className="flex justify-between text-xs">
                          <span className="text-gray-500">{formatDateTime(history.created_at)}</span>
                          <span className="font-medium">
                            {history.old_score} → {history.new_score}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Status Management */}
              <div className="bg-white rounded-lg shadow-md p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Update Status</h3>
                <div className="space-y-4">
                  <select
                    value={selectedStatus}
                    onChange={(e) => setSelectedStatus(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    disabled={updatingStatus}
                  >
                    <option value="pending">Pending</option>
                    <option value="contacted">Contacted</option>
                    <option value="qualified">Qualified</option>
                    <option value="converted">Converted</option>
                    <option value="lost">Lost</option>
                  </select>
                  <button
                    onClick={handleStatusChange}
                    disabled={updatingStatus || selectedStatus === lead.status_code}
                    className="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {updatingStatus ? 'Updating...' : 'Update Status'}
                  </button>
                </div>
              </div>

              {/* Quick Actions */}
              <div className="bg-white rounded-lg shadow-md p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div className="space-y-2">
                  {lead.email && (
                    <a
                      href={`mailto:${lead.email}`}
                      className="block w-full px-4 py-2 text-center border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                    >
                      Send Email
                    </a>
                  )}
                  {lead.phone && (
                    <a
                      href={`tel:${lead.phone}`}
                      className="block w-full px-4 py-2 text-center border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                    >
                      Call Lead
                    </a>
                  )}
                  <button
                    onClick={() => setActiveTab('whatsapp')}
                    className="block w-full px-4 py-2 text-center border border-green-500 text-green-600 rounded-md hover:bg-green-50 flex items-center justify-center gap-2"
                  >
                    <span className="text-lg">💬</span> Open WhatsApp Chat
                  </button>

                  <div className="pt-4 border-t border-gray-200 mt-4">
                    <label className={`flex items-center justify-center w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer ${uploadingFile ? 'opacity-50 cursor-not-allowed' : ''}`}>
                      {uploadingFile ? (
                        <span className="flex items-center gap-2">
                          <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          Uploading...
                        </span>
                      ) : (
                        <span className="flex items-center gap-2">
                          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                          Upload Screenshot
                        </span>
                      )}
                      <input
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={handleFileUpload}
                        disabled={uploadingFile}
                      />
                    </label>
                    <p className="text-xs text-gray-500 text-center mt-2">Upload chat screenshots for the record</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main >
    </div >
  );
}

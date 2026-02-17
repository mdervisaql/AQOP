import { useState, useEffect, useRef } from 'react';
import { whatsappApi } from '../api/whatsapp';

export default function WhatsAppChat({ leadId, leadPhone }) {
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState(null);
    const messagesEndRef = useRef(null);

    // Poll for new messages every 10 seconds
    useEffect(() => {
        fetchMessages();
        const interval = setInterval(fetchMessages, 10000);
        return () => clearInterval(interval);
    }, [leadId]);

    // Scroll to bottom when messages change
    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const fetchMessages = async () => {
        try {
            const data = await whatsappApi.getMessages(leadId);
            setMessages(data);
            setError(null);
        } catch (err) {
            console.error('Failed to fetch messages', err);
            // Don't show error on polling if it was working before
            if (loading) setError('Failed to load chat history.');
        } finally {
            setLoading(false);
        }
    };

    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim()) return;

        setSending(true);
        try {
            await whatsappApi.sendMessage({
                lead_id: leadId,
                message: newMessage,
                type: 'text',
            });
            setNewMessage('');
            fetchMessages(); // Refresh immediately
        } catch (err) {
            setError('Failed to send message. Please try again.');
        } finally {
            setSending(false);
        }
    };

    const formatTime = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500"></div>
            </div>
        );
    }

    return (
        <div className="flex flex-col h-[600px] bg-[#e5ddd5] rounded-lg shadow-lg overflow-hidden border border-gray-200">
            {/* Header */}
            <div className="bg-[#075e54] text-white p-4 flex items-center justify-between shadow-md">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">
                        <span className="dashicons dashicons-whatsapp text-2xl"></span>
                    </div>
                    <div>
                        <h3 className="font-bold text-lg">WhatsApp Chat</h3>
                        <p className="text-xs opacity-80">{leadPhone}</p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <span className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <span className="text-xs">Live</span>
                </div>
            </div>

            {/* Messages Area */}
            <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat">
                {messages.length === 0 ? (
                    <div className="text-center text-gray-500 my-10 bg-white/80 p-4 rounded-lg inline-block mx-auto shadow-sm">
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                ) : (
                    messages.map((msg) => (
                        <div
                            key={msg.id}
                            className={`flex ${msg.direction === 'outbound' ? 'justify-end' : 'justify-start'}`}
                        >
                            <div
                                className={`max-w-[70%] rounded-lg p-3 shadow-sm relative ${msg.direction === 'outbound'
                                        ? 'bg-[#dcf8c6] rounded-tr-none'
                                        : 'bg-white rounded-tl-none'
                                    }`}
                            >
                                <p className="text-gray-800 text-sm whitespace-pre-wrap">{msg.content}</p>
                                <div className="flex items-center justify-end gap-1 mt-1">
                                    <span className="text-[10px] text-gray-500">
                                        {formatTime(msg.created_at)}
                                    </span>
                                    {msg.direction === 'outbound' && (
                                        <span className={`text-[10px] ${msg.status === 'read' ? 'text-blue-500' : 'text-gray-400'}`}>
                                            {msg.status === 'read' ? '✓✓' : msg.status === 'delivered' ? '✓✓' : '✓'}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* Error Message */}
            {error && (
                <div className="bg-red-100 border-l-4 border-red-500 text-red-700 p-2 text-sm mx-4 mt-2">
                    <p>{error}</p>
                </div>
            )}

            {/* Input Area */}
            <form onSubmit={handleSendMessage} className="bg-[#f0f0f0] p-3 flex items-center gap-2 border-t border-gray-300">
                <button
                    type="button"
                    className="p-2 text-gray-500 hover:text-gray-700 transition-colors"
                    title="Attach (Coming Soon)"
                >
                    <span className="text-xl">📎</span>
                </button>
                <input
                    type="text"
                    value={newMessage}
                    onChange={(e) => setNewMessage(e.target.value)}
                    placeholder="Type a message..."
                    className="flex-1 py-2 px-4 rounded-full border border-gray-300 focus:outline-none focus:border-[#075e54] focus:ring-1 focus:ring-[#075e54]"
                    disabled={sending}
                />
                <button
                    type="submit"
                    disabled={sending || !newMessage.trim()}
                    className={`p-3 rounded-full text-white shadow-md transition-all ${sending || !newMessage.trim()
                            ? 'bg-gray-400 cursor-not-allowed'
                            : 'bg-[#075e54] hover:bg-[#128c7e]'
                        }`}
                >
                    {sending ? (
                        <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    ) : (
                        <span className="text-xl">➤</span>
                    )}
                </button>
            </form>
        </div>
    );
}

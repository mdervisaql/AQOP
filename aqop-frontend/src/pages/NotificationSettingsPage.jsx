import React from 'react';
import Navigation from '../components/Navigation';
import PushNotificationSettings from '../components/PushNotificationSettings';

export default function NotificationSettingsPage() {
    return (
        <div className="flex h-screen bg-gray-50">
            <Navigation currentPage="settings" />

            <main className="flex-1 overflow-y-auto">
                <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                    <div className="px-4 py-6 sm:px-0">
                        <h1 className="text-2xl font-bold text-gray-900 mb-6">Notification Settings</h1>

                        <div className="space-y-6">
                            <PushNotificationSettings />

                            {/* Placeholder for other settings (Email, Telegram) if we implement frontend UI for them later */}
                            {/* 
              <div className="bg-white shadow rounded-lg p-6">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Other Channels</h3>
                <p className="text-gray-500">Email and Telegram settings are currently managed by the administrator.</p>
              </div> 
              */}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}

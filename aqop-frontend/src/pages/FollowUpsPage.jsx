import { useState } from 'react';
import Navigation from '../components/Navigation';
import FollowUpsList from '../components/FollowUpsList';

export default function FollowUpsPage() {
    return (
        <div className="flex h-screen bg-gray-50">
            <Navigation currentPage="follow-ups" />

            <main className="flex-1 overflow-y-auto">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Follow-up Tasks</h1>
                    <FollowUpsList />
                </div>
            </main>
        </div>
    );
}

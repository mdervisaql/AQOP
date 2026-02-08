import React from 'react';

/**
 * LeadScore Component
 * Displays a lead's score as stars and a colored badge.
 * 
 * @param {Object} props
 * @param {number} props.score - The lead score (0-100)
 * @param {string} props.rating - The lead rating (hot, warm, qualified, cold, not_interested)
 * @param {boolean} props.showLabel - Whether to show the text label
 * @param {boolean} props.size - Size of the component (sm, md, lg)
 */
const LeadScore = ({ score, rating, showLabel = true, size = 'md' }) => {

    const getRatingColor = (rating) => {
        switch (rating) {
            case 'hot': return 'text-red-500';
            case 'warm': return 'text-orange-500';
            case 'qualified': return 'text-yellow-500';
            case 'cold': return 'text-blue-400';
            case 'not_interested': return 'text-gray-400';
            default: return 'text-gray-300';
        }
    };

    const getRatingBg = (rating) => {
        switch (rating) {
            case 'hot': return 'bg-red-100 text-red-800';
            case 'warm': return 'bg-orange-100 text-orange-800';
            case 'qualified': return 'bg-yellow-100 text-yellow-800';
            case 'cold': return 'bg-blue-100 text-blue-800';
            case 'not_interested': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getStars = (score) => {
        // 5 stars total
        // 0-19: 1 star
        // 20-39: 2 stars
        // 40-59: 3 stars
        // 60-79: 4 stars
        // 80-100: 5 stars
        const numStars = Math.floor(score / 20) + (score > 0 ? 1 : 0);
        const stars = Math.min(5, Math.max(1, numStars));

        return '★'.repeat(stars) + '☆'.repeat(5 - stars);
    };

    const colorClass = getRatingColor(rating);
    const bgClass = getRatingBg(rating);

    const sizeClasses = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-lg'
    };

    return (
        <div className={`flex items-center gap-2 ${sizeClasses[size]}`}>
            <span className={`font-bold tracking-widest ${colorClass}`} title={`Score: ${score}/100`}>
                {getStars(score)}
            </span>
            {showLabel && (
                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${bgClass}`}>
                    {rating ? rating.replace('_', ' ').toUpperCase() : 'N/A'}
                </span>
            )}
        </div>
    );
};

export default LeadScore;

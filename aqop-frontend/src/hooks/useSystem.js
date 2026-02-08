import { useQuery } from '@tanstack/react-query';
import { getSystemHealth, getSystemStats } from '../api/system';

export const systemKeys = {
    all: ['system'],
    health: () => [...systemKeys.all, 'health'],
    stats: (days) => [...systemKeys.all, 'stats', days],
};

export const useSystemHealth = (options = {}) => {
    return useQuery({
        queryKey: systemKeys.health(),
        queryFn: getSystemHealth,
        refetchInterval: 30000, // Refresh every 30 seconds
        ...options,
    });
};

export const useSystemStats = (days = 7, options = {}) => {
    return useQuery({
        queryKey: systemKeys.stats(days),
        queryFn: () => getSystemStats(days),
        keepPreviousData: true,
        ...options,
    });
};

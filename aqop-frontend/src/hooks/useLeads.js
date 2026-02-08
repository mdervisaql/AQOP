import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getLeads,
    getMyLeads,
    getLead,
    getLeadsStats,
    createLead,
    updateLead,
    deleteLead,
    addLeadNote,
    getLeadNotes
} from '../api/leads';

// Query Keys
export const leadKeys = {
    all: ['leads'],
    lists: () => [...leadKeys.all, 'list'],
    list: (filters) => [...leadKeys.lists(), { ...filters }],
    details: () => [...leadKeys.all, 'detail'],
    detail: (id) => [...leadKeys.details(), id],
    stats: () => [...leadKeys.all, 'stats'],
    notes: (id) => [...leadKeys.detail(id), 'notes'],
};

// --- Queries ---

export const useLeads = (filters = {}, isAgent = false, options = {}) => {
    return useQuery({
        queryKey: leadKeys.list({ ...filters, isAgent }),
        queryFn: () => isAgent ? getMyLeads(filters) : getLeads(filters),
        keepPreviousData: true,
        ...options,
    });
};

export const useLead = (id) => {
    return useQuery({
        queryKey: leadKeys.detail(id),
        queryFn: () => getLead(id),
        enabled: !!id,
    });
};

export const useLeadsStats = (options = {}) => {
    return useQuery({
        queryKey: leadKeys.stats(),
        queryFn: getLeadsStats,
        ...options,
    });
};

export const useLeadNotes = (id) => {
    return useQuery({
        queryKey: leadKeys.notes(id),
        queryFn: () => getLeadNotes(id),
        enabled: !!id,
    });
};

// --- Mutations ---

export const useCreateLead = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: createLead,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: leadKeys.lists() });
            queryClient.invalidateQueries({ queryKey: leadKeys.stats() });
        },
    });
};

export const useUpdateLead = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, data }) => updateLead(id, data),
        onSuccess: (data, variables) => {
            queryClient.invalidateQueries({ queryKey: leadKeys.detail(variables.id) });
            queryClient.invalidateQueries({ queryKey: leadKeys.lists() });
            queryClient.invalidateQueries({ queryKey: leadKeys.stats() });
        },
    });
};

export const useDeleteLead = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: deleteLead,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: leadKeys.lists() });
            queryClient.invalidateQueries({ queryKey: leadKeys.stats() });
        },
    });
};

export const useAddLeadNote = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, note }) => addLeadNote(id, note),
        onSuccess: (data, variables) => {
            queryClient.invalidateQueries({ queryKey: leadKeys.notes(variables.id) });
        },
    });
};

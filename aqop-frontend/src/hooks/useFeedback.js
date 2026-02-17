import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getFeedback,
    getFeedbackItem,
    createFeedback,
    updateFeedback,
    addFeedbackComment,
    getFeedbackComments,
    getFeedbackStats,
} from '../api/feedback';

export const feedbackKeys = {
    all: ['feedback'],
    lists: () => [...feedbackKeys.all, 'list'],
    list: (filters) => [...feedbackKeys.lists(), filters],
    details: () => [...feedbackKeys.all, 'detail'],
    detail: (id) => [...feedbackKeys.details(), id],
    comments: (id) => [...feedbackKeys.all, 'comments', id],
    stats: () => [...feedbackKeys.all, 'stats'],
};

export const useFeedback = (filters = {}, options = {}) => {
    return useQuery({
        queryKey: feedbackKeys.list(filters),
        queryFn: () => getFeedback(filters),
        ...options,
    });
};

export const useFeedbackItem = (id, options = {}) => {
    return useQuery({
        queryKey: feedbackKeys.detail(id),
        queryFn: () => getFeedbackItem(id),
        enabled: !!id,
        ...options,
    });
};

export const useFeedbackComments = (id, options = {}) => {
    return useQuery({
        queryKey: feedbackKeys.comments(id),
        queryFn: () => getFeedbackComments(id),
        enabled: !!id,
        ...options,
    });
};

export const useFeedbackStats = (options = {}) => {
    return useQuery({
        queryKey: feedbackKeys.stats(),
        queryFn: getFeedbackStats,
        ...options,
    });
};

export const useCreateFeedback = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: createFeedback,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: feedbackKeys.lists() });
            queryClient.invalidateQueries({ queryKey: feedbackKeys.stats() });
        },
    });
};

export const useUpdateFeedback = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, data }) => updateFeedback(id, data),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: feedbackKeys.lists() });
            queryClient.invalidateQueries({ queryKey: feedbackKeys.detail(variables.id) });
            queryClient.invalidateQueries({ queryKey: feedbackKeys.stats() });
        },
    });
};

export const useAddComment = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, comment_text, is_internal }) => addFeedbackComment(id, comment_text, is_internal),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: feedbackKeys.comments(variables.id) });
            queryClient.invalidateQueries({ queryKey: feedbackKeys.detail(variables.id) });
        },
    });
};

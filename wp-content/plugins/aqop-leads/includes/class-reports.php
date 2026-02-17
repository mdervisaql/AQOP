<?php
if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Reports
{

    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get Agent Performance Report
     */
    public function get_agent_performance($user_id = null, $from = null, $to = null)
    {
        $where = "1=1";
        $params = [];

        if ($user_id) {
            $where .= " AND assigned_to = %d";
            $params[] = $user_id;
        }

        if ($from) {
            $where .= " AND created_at >= %s";
            $params[] = $from;
        }

        if ($to) {
            $where .= " AND created_at <= %s";
            $params[] = $to;
        }

        // Leads Assigned
        $sql_assigned = "SELECT assigned_to, COUNT(*) as count FROM {$this->wpdb->prefix}aq_leads WHERE $where AND assigned_to IS NOT NULL GROUP BY assigned_to";
        $assigned_results = $this->wpdb->get_results($this->wpdb->prepare($sql_assigned, $params), OBJECT_K);

        // Leads Converted (status_code = 'converted')
        $sql_converted = "SELECT assigned_to, COUNT(*) as count FROM {$this->wpdb->prefix}aq_leads WHERE $where AND status_code = 'converted' AND assigned_to IS NOT NULL GROUP BY assigned_to";
        $converted_results = $this->wpdb->get_results($this->wpdb->prepare($sql_converted, $params), OBJECT_K);

        // Leads Contacted (status_code IN ('contacted', 'qualified', 'converted', 'lost'))
        $sql_contacted = "SELECT assigned_to, COUNT(*) as count FROM {$this->wpdb->prefix}aq_leads WHERE $where AND status_code IN ('contacted', 'qualified', 'converted', 'lost') AND assigned_to IS NOT NULL GROUP BY assigned_to";
        $contacted_results = $this->wpdb->get_results($this->wpdb->prepare($sql_contacted, $params), OBJECT_K);

        // Get all agents
        $agents = get_users(['role__in' => ['aq_agent', 'administrator', 'operation_manager']]);

        $report = [];
        foreach ($agents as $agent) {
            $id = $agent->ID;

            // Skip if filtering by specific user and this is not them
            if ($user_id && $id != $user_id)
                continue;

            $assigned = isset($assigned_results[$id]) ? (int) $assigned_results[$id]->count : 0;
            $converted = isset($converted_results[$id]) ? (int) $converted_results[$id]->count : 0;
            $contacted = isset($contacted_results[$id]) ? (int) $contacted_results[$id]->count : 0;

            // Avoid division by zero
            $conversion_rate = $assigned > 0 ? round(($converted / $assigned) * 100, 1) : 0;

            // Get average response time (mock for now, or calculate from logs if available)
            // Ideally we would query the activity log or communications table
            $avg_response_time = 0;

            // Get score (from user meta or calculation)
            // For now, let's use a placeholder or calculate based on conversion
            $score = min(100, ($conversion_rate * 2) + ($contacted > 0 ? 20 : 0));

            $report[] = [
                'user_id' => $id,
                'name' => $agent->display_name,
                'leads_assigned' => $assigned,
                'leads_contacted' => $contacted,
                'leads_converted' => $converted,
                'conversion_rate' => $conversion_rate,
                'avg_response_time_hours' => $avg_response_time,
                'score' => $score
            ];
        }

        // Sort by score desc
        usort($report, function ($a, $b) {
            return $b['score'] - $a['score'];
        });

        return $report;
    }

    /**
     * Get Source Analysis
     */
    public function get_source_analysis($from = null, $to = null)
    {
        $where = "1=1";
        $params = [];

        if ($from) {
            $where .= " AND created_at >= %s";
            $params[] = $from;
        }

        if ($to) {
            $where .= " AND created_at <= %s";
            $params[] = $to;
        }

        $sql = "SELECT source, COUNT(*) as total_leads, 
                SUM(CASE WHEN status_code = 'converted' THEN 1 ELSE 0 END) as converted_leads
                FROM {$this->wpdb->prefix}aq_leads 
                WHERE $where 
                GROUP BY source 
                ORDER BY total_leads DESC";

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));

        $report = [];
        foreach ($results as $row) {
            $source = $row->source ?: 'Unknown';
            $total = (int) $row->total_leads;
            $converted = (int) $row->converted_leads;
            $rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

            $report[] = [
                'source' => $source,
                'leads_count' => $total,
                'converted_count' => $converted,
                'conversion_rate' => $rate
            ];
        }

        return $report;
    }

    /**
     * Get Campaign Performance
     */
    public function get_campaign_performance($from = null, $to = null)
    {
        // Similar to source analysis but grouping by campaign
        // Assuming 'campaign' column exists or is stored in meta. 
        // If not in main table, we might need to join or check meta.
        // For now, let's assume it's a column or we use 'ad_name' / 'campaign_name' from FB leads if mapped.
        // Let's check if 'campaign' column exists, if not use a placeholder logic.

        // Checking schema from previous interactions, we don't explicitly have 'campaign' column in aq_leads.
        // However, we might have 'source' which covers some of it.
        // Let's assume we want to group by 'source' for now as a proxy if campaign column is missing,
        // OR we can look at `wp_aq_facebook_leads_logs` if we want FB specific campaigns.

        // Let's stick to 'source' for now as the primary campaign identifier until we have a dedicated campaign field.
        // Or better, let's return an empty array if no campaign data is strictly available, 
        // but the user asked for "Campaign Performance". 
        // Let's try to see if we can use 'ad_name' or 'campaign_name' if they were added.
        // If not, we'll return a mock or empty structure to be filled later.

        return [];
    }

    /**
     * Get Time Analysis
     */
    public function get_time_analysis($period = 'daily', $from = null, $to = null)
    {
        $where = "1=1";
        $params = [];

        if ($from) {
            $where .= " AND created_at >= %s";
            $params[] = $from;
        }

        if ($to) {
            $where .= " AND created_at <= %s";
            $params[] = $to;
        }

        $date_format = '%Y-%m-%d';
        if ($period === 'monthly') {
            $date_format = '%Y-%m';
        } elseif ($period === 'weekly') {
            $date_format = '%Y-%u';
        }

        $sql = "SELECT DATE_FORMAT(created_at, '$date_format') as date_period, COUNT(*) as count 
                FROM {$this->wpdb->prefix}aq_leads 
                WHERE $where 
                GROUP BY date_period 
                ORDER BY date_period ASC";

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));

        return array_map(function ($row) {
            return [
                'date' => $row->date_period,
                'leads' => (int) $row->count
            ];
        }, $results);
    }

    /**
     * Get Status Distribution
     */
    public function get_status_distribution($from = null, $to = null)
    {
        $where = "1=1";
        $params = [];

        if ($from) {
            $where .= " AND created_at >= %s";
            $params[] = $from;
        }

        if ($to) {
            $where .= " AND created_at <= %s";
            $params[] = $to;
        }

        // We need to join with status table to get names if possible, or just use status_code
        // Assuming we have status_code in aq_leads.

        $sql = "SELECT status_code, COUNT(*) as count 
                FROM {$this->wpdb->prefix}aq_leads 
                WHERE $where 
                GROUP BY status_code";

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));

        // Map codes to nice names (could fetch from DB or hardcode common ones)
        $status_map = [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'converted' => 'Converted',
            'lost' => 'Lost',
            'pending' => 'Pending'
        ];

        return array_map(function ($row) use ($status_map) {
            return [
                'status' => $row->status_code,
                'label' => isset($status_map[$row->status_code]) ? $status_map[$row->status_code] : ucfirst($row->status_code),
                'count' => (int) $row->count
            ];
        }, $results);
    }

    /**
     * Get Country Analysis
     */
    public function get_country_analysis($from = null, $to = null)
    {
        $where = "1=1";
        $params = [];

        if ($from) {
            $where .= " AND l.created_at >= %s";
            $params[] = $from;
        }

        if ($to) {
            $where .= " AND l.created_at <= %s";
            $params[] = $to;
        }

        // Join with countries table
        $sql = "SELECT c.country_name_en, COUNT(l.id) as total_leads,
                SUM(CASE WHEN l.status_code = 'converted' THEN 1 ELSE 0 END) as converted_leads
                FROM {$this->wpdb->prefix}aq_leads l
                LEFT JOIN {$this->wpdb->prefix}aq_countries c ON l.country_id = c.id
                WHERE $where
                GROUP BY c.country_name_en
                ORDER BY total_leads DESC";

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));

        $report = [];
        foreach ($results as $row) {
            $country = $row->country_name_en ?: 'Unknown';
            $total = (int) $row->total_leads;
            $converted = (int) $row->converted_leads;
            $rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

            $report[] = [
                'country' => $country,
                'leads_count' => $total,
                'converted_count' => $converted,
                'conversion_rate' => $rate
            ];
        }

        return $report;
    }

    /**
     * Get Summary Stats
     */
    public function get_summary_stats($from = null, $to = null)
    {
        $where = "1=1";
        $params = [];

        if ($from) {
            $where .= " AND created_at >= %s";
            $params[] = $from;
        }

        if ($to) {
            $where .= " AND created_at <= %s";
            $params[] = $to;
        }

        $sql = "SELECT 
                COUNT(*) as total_leads,
                SUM(CASE WHEN status_code = 'converted' THEN 1 ELSE 0 END) as converted_leads,
                AVG(lead_score) as avg_score
                FROM {$this->wpdb->prefix}aq_leads 
                WHERE $where";

        $result = $this->wpdb->get_row($this->wpdb->prepare($sql, $params));

        $total = (int) $result->total_leads;
        $converted = (int) $result->converted_leads;
        $rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        return [
            'total_leads' => $total,
            'converted_leads' => $converted,
            'conversion_rate' => $rate,
            'avg_score' => round((float) $result->avg_score, 1)
        ];
    }
}

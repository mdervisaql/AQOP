<?php
/**
 * Airtable Smart Sync functionality
 *
 * @package AQOP_Leads
 * @since 1.0.9
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * AQOP Airtable Sync class
 */
class AQOP_Airtable_Sync
{
	/**
	 * Cache for countries
	 * @var array
	 */
	private $countries_cache = array();

	/**
	 * Cache for campaign groups
	 * @var array
	 */
	private $groups_cache = array();

	/**
	 * Cache for campaigns
	 * @var array
	 */
	private $campaigns_cache = array();

	/**
	 * Cache for sources
	 * @var array
	 */
	private $sources_cache = array();

	/**
	 * Debug mode - set to true only when troubleshooting sync issues
	 * @var bool
	 */
	private $debug = false;

	/**
	 * Airtable API base URL
	 */
	const API_BASE_URL = 'https://api.airtable.com/v0/';

	/**
	 * Queue of Airtable record IDs to mark as synced
	 * @var array
	 */
	private $sync_queue = array();

	/**
	 * Mark a record as synced in Airtable by setting sync_with_aqop = true
	 *
	 * @param string $record_id Airtable record ID
	 * @return bool Success status
	 */
	private function mark_as_synced($record_id)
	{
		$api_key = get_option('aqop_airtable_token', '');
		$base_id = get_option('aqop_airtable_base_id', '');
		$table_name = get_option('aqop_airtable_table_name', '');

		if (empty($api_key) || empty($base_id) || empty($table_name) || empty($record_id)) {
			return false;
		}

		$url = self::API_BASE_URL . $base_id . '/' . rawurlencode($table_name) . '/' . $record_id;

		$response = wp_remote_request($url, array(
			'method' => 'PATCH',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode(array(
				'fields' => array(
					'sync_with_aqop' => true,
				),
			)),
			'timeout' => 30,
		));

		if (is_wp_error($response)) {
			error_log('AQOP Smart Sync: Failed to mark record ' . $record_id . ' as synced: ' . $response->get_error_message());
			return false;
		}

		$status_code = wp_remote_retrieve_response_code($response);
		if ($status_code !== 200) {
			error_log('AQOP Smart Sync: Failed to mark record ' . $record_id . ' as synced. Status: ' . $status_code);
			return false;
		}

		return true;
	}

	/**
	 * Batch mark multiple records as synced in Airtable (max 10 per request)
	 *
	 * @param array $record_ids Array of Airtable record IDs
	 * @return int Number of successfully marked records
	 */
	private function batch_mark_as_synced($record_ids)
	{
		if (empty($record_ids)) {
			return 0;
		}

		$api_key = get_option('aqop_airtable_token', '');
		$base_id = get_option('aqop_airtable_base_id', '');
		$table_name = get_option('aqop_airtable_table_name', '');

		if (empty($api_key) || empty($base_id) || empty($table_name)) {
			return 0;
		}

		$marked_count = 0;

		// Airtable batch update allows max 10 records per request
		$chunks = array_chunk($record_ids, 10);

		foreach ($chunks as $chunk) {
			$records = array();
			foreach ($chunk as $record_id) {
				$records[] = array(
					'id' => $record_id,
					'fields' => array(
						'sync_with_aqop' => true,
					),
				);
			}

			$url = self::API_BASE_URL . $base_id . '/' . rawurlencode($table_name);

			$response = wp_remote_request($url, array(
				'method' => 'PATCH',
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode(array(
					'records' => $records,
				)),
				'timeout' => 30,
			));

			if (is_wp_error($response)) {
				error_log('AQOP Smart Sync: Batch mark failed: ' . $response->get_error_message());
				continue;
			}

			$status_code = wp_remote_retrieve_response_code($response);
			if ($status_code === 200) {
				$marked_count += count($chunk);
			} else {
				error_log('AQOP Smart Sync: Batch mark failed. Status: ' . $status_code);
			}
		}

		return $marked_count;
	}

	/**
	 * Sync from Airtable to WordPress
	 *
	 * @return array Sync results
	 */
	public function sync_from_airtable()
	{
		// Prefetch related data to minimize database queries
		$this->prefetch_related_data();
		// Increase execution time for large syncs
		@set_time_limit(300); // 5 minutes
		@ini_set('max_execution_time', 300);

		// Increase memory limit
		@ini_set('memory_limit', '256M');

		$results = array(
			'success' => false,
			'message' => '',
			'leads_processed' => 0,
			'leads_created' => 0,
			'leads_updated' => 0,
			'countries_created' => 0,
			'campaigns_created' => 0,
			'groups_created' => 0,
			'sources_created' => 0,
			'errors' => array(),
		);

		try {
			// Get Airtable credentials
			$api_key = get_option('aqop_airtable_token', '');
			$base_id = get_option('aqop_airtable_base_id', '');
			$table_name = get_option('aqop_airtable_table_name', '');

			if (empty($api_key) || empty($base_id) || empty($table_name)) {
				throw new Exception('Airtable credentials not configured');
			}

			// Fetch all records from Airtable
			$records = $this->fetch_airtable_records($api_key, $base_id, $table_name);

			if (empty($records)) {
				throw new Exception('No records found in Airtable');
			}

			$results['leads_processed'] = count($records);

			// Process records in batches
			$batch_size = 100;
			$batches = array_chunk($records, $batch_size);

			foreach ($batches as $batch) {
				try {
					$this->process_batch($batch, $results);
				} catch (Exception $e) {
					$results['errors'][] = 'Batch processing error: ' . $e->getMessage();
				}
			}

			$results['success'] = true;
			$results['message'] = sprintf(
				'Sync completed: %d leads processed, %d created, %d updated',
				$results['leads_processed'],
				$results['leads_created'],
				$results['leads_updated']
			);

			// Update last sync time
			update_option('aqop_airtable_last_sync', current_time('mysql'));
			update_option('aqop_airtable_sync_stats', $results);

		} catch (Exception $e) {
			$results['message'] = 'Sync failed: ' . $e->getMessage();
			$results['errors'][] = $e->getMessage();
		}

		return $results;
	}

	/**
	 * Test sync from Airtable (10 records only) with detailed analysis
	 *
	 * @return array Test sync results with detailed field info
	 */
	public function test_sync_from_airtable()
	{
		$this->prefetch_related_data();

		$results = array(
			'success' => false,
			'message' => '',
			'records_fetched' => 0,
			'records_with_name' => 0,
			'records_with_email' => 0,
			'records_with_phone' => 0,
			'records_with_country' => 0,
			'records_with_campaign' => 0,
			'sample_records' => array(),
			'errors' => array(),
		);

		try {
			// Get Airtable credentials
			$api_key = get_option('aqop_airtable_token', '');
			$base_id = get_option('aqop_airtable_base_id', '');
			$table_name = get_option('aqop_airtable_table_name', '');

			if (empty($api_key) || empty($base_id) || empty($table_name)) {
				throw new Exception('Airtable credentials not configured');
			}

			// Fetch only first 10 records
			$url = self::API_BASE_URL . $base_id . '/' . rawurlencode($table_name) . '?pageSize=10';
			$response = wp_remote_get($url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 30,
			));

			if (is_wp_error($response)) {
				throw new Exception('Airtable API Error: ' . $response->get_error_message());
			}

			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);

			if (isset($data['error'])) {
				$msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
				throw new Exception('Airtable API Error: ' . $msg);
			}

			$records = isset($data['records']) ? $data['records'] : array();
			$results['records_fetched'] = count($records);

			// Analyze each record
			foreach ($records as $record) {
				$mapped_data = $this->map_airtable_record($record);

				// Check for fields
				$has_name = !empty($mapped_data['lead_name']) || !empty($mapped_data['name']);
				$has_email = !empty($mapped_data['email']);
				$has_phone = !empty($mapped_data['phone']);
				$has_country = !empty($mapped_data['country_name']);
				$has_campaign = !empty($mapped_data['campaign_name']);

				if ($has_name)
					$results['records_with_name']++;
				if ($has_email)
					$results['records_with_email']++;
				if ($has_phone)
					$results['records_with_phone']++;
				if ($has_country)
					$results['records_with_country']++;
				if ($has_campaign)
					$results['records_with_campaign']++;

				// Store sample record info (first 5 only)
				if (count($results['sample_records']) < 5) {
					$results['sample_records'][] = array(
						'airtable_id' => $record['id'],
						'name' => isset($mapped_data['lead_name']) ? $mapped_data['lead_name'] : (isset($mapped_data['name']) ? $mapped_data['name'] : ''),
						'email' => isset($mapped_data['email']) ? $mapped_data['email'] : '',
						'phone' => isset($mapped_data['phone']) ? substr($mapped_data['phone'], 0, 10) . '...' : '',
						'country' => isset($mapped_data['country_name']) ? $mapped_data['country_name'] : '',
						'campaign' => isset($mapped_data['campaign_name']) ? $mapped_data['campaign_name'] : '',
					);
				}
			}

			$results['success'] = true;
			$results['message'] = sprintf(
				'Test completed: %d records fetched. Names: %d, Emails: %d, Phones: %d',
				$results['records_fetched'],
				$results['records_with_name'],
				$results['records_with_email'],
				$results['records_with_phone']
			);

		} catch (Exception $e) {
			$results['message'] = 'Test sync failed: ' . $e->getMessage();
			$results['errors'][] = $e->getMessage();
		}

		return $results;
	}

	/**
	 * Prefetch related data to minimize database queries
	 */
	private function prefetch_related_data()
	{
		global $wpdb;

		// 1. Cache Countries
		$countries = $wpdb->get_results("SELECT id, country_name_en, country_code FROM {$wpdb->prefix}aq_dim_countries");
		foreach ($countries as $country) {
			$this->countries_cache[strtolower($country->country_name_en)] = $country->id;
		}

		// 2. Cache Campaign Groups
		$groups = $wpdb->get_results("SELECT id, group_name_en FROM {$wpdb->prefix}aq_campaign_groups");
		foreach ($groups as $group) {
			$this->groups_cache[strtolower($group->group_name_en)] = $group->id;
		}

		// 3. Cache Campaigns
		$campaigns = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}aq_leads_campaigns");
		foreach ($campaigns as $campaign) {
			$this->campaigns_cache[strtolower($campaign->name)] = $campaign->id;
		}

		// 4. Cache Sources
		$sources = $wpdb->get_results("SELECT id, source_name, source_code FROM {$wpdb->prefix}aq_leads_sources");
		foreach ($sources as $source) {
			$this->sources_cache[strtolower($source->source_name)] = $source->id;
			// Also cache by code if needed, but name is primary match from Airtable
		}
	}

	/**
	 * Fetch all records from Airtable (handling pagination)
	 *
	 * @param string $api_key API Key
	 * @param string $base_id Base ID
	 * @param string $table_name Table Name
	 * @return array All records
	 */
	private function fetch_airtable_records($api_key, $base_id, $table_name)
	{
		$records = array();
		$offset = '';

		do {
			$url = self::API_BASE_URL . $base_id . '/' . rawurlencode($table_name) . '?pageSize=100';
			if (!empty($offset)) {
				$url .= '&offset=' . $offset;
			}

			$response = wp_remote_get($url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 60, // Increase timeout for each request
			));

			if (is_wp_error($response)) {
				throw new Exception('Airtable API Error: ' . $response->get_error_message());
			}

			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);

			if (isset($data['error'])) {
				$msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
				throw new Exception('Airtable API Error: ' . $msg);
			}

			if (isset($data['records']) && is_array($data['records'])) {
				$records = array_merge($records, $data['records']);
			}

			$offset = isset($data['offset']) ? $data['offset'] : '';

			// Optional: Sleep briefly to avoid rate limits if processing many pages
			if (!empty($offset)) {
				usleep(200000); // 0.2 seconds
			}

		} while (!empty($offset));

		return $records;

	}

	/**
	 * Process a batch of Airtable records
	 *
	 * @param array $batch Batch of records
	 * @param array &$results Results array (passed by reference)
	 */
	private function process_batch($batch, &$results)
	{
		global $wpdb;

		$prepared_leads = array();
		$emails = array();
		$airtable_ids = array();
		$synced_record_ids = array(); // Track successfully synced Airtable record IDs

		// 1. Prepare data for all records in the batch
		foreach ($batch as $record) {
			try {
				$lead_data = $this->prepare_lead_data($record, $results);
				if ($lead_data) {
					// Store original Airtable record ID for marking
					$lead_data['_original_airtable_id'] = $record['id'];
					$prepared_leads[] = $lead_data;
					if (!empty($lead_data['email'])) {
						$emails[] = $lead_data['email'];
					}
					if (!empty($lead_data['airtable_record_id'])) {
						$airtable_ids[] = $lead_data['airtable_record_id'];
					}
				}
			} catch (Exception $e) {
				$results['errors'][] = 'Record ' . $record['id'] . ': ' . $e->getMessage();
			}
		}

		if (empty($prepared_leads)) {
			return;
		}

		// 2. Check for existing leads by email OR airtable_record_id
		$existing_leads = $this->get_existing_leads_map($emails, $airtable_ids);

		$email_map = $existing_leads['by_email'];
		$id_map = $existing_leads['by_airtable_id'];

		$to_insert = array();
		$to_update = array();

		// 3. Separate into inserts and updates
		foreach ($prepared_leads as $lead) {
			$email = isset($lead['email']) ? $lead['email'] : '';
			$airtable_id = isset($lead['airtable_record_id']) ? $lead['airtable_record_id'] : '';
			$original_airtable_id = isset($lead['_original_airtable_id']) ? $lead['_original_airtable_id'] : '';

			// Remove the internal tracking field before database operations
			unset($lead['_original_airtable_id']);

			$existing_id = null;

			// Check by Airtable ID first (strongest match)
			if (!empty($airtable_id) && isset($id_map[$airtable_id])) {
				$existing_id = $id_map[$airtable_id];
			}
			// Fallback to Email match
			elseif (!empty($email) && isset($email_map[$email])) {
				$existing_id = $email_map[$email];
			}

			if ($existing_id) {
				// Update existing
				$lead['id'] = $existing_id;
				$lead['_original_airtable_id'] = $original_airtable_id; // Re-add for tracking
				$to_update[] = $lead;
			} else {
				// Insert new
				$lead['_original_airtable_id'] = $original_airtable_id; // Re-add for tracking
				$to_insert[] = $lead;
			}
		}

		// 4. Bulk Insert
		if (!empty($to_insert)) {
			// Collect IDs for marking before removing internal field
			$insert_record_ids = array();
			foreach ($to_insert as &$lead) {
				if (!empty($lead['_original_airtable_id'])) {
					$insert_record_ids[] = $lead['_original_airtable_id'];
				}
				unset($lead['_original_airtable_id']);
			}
			unset($lead);

			$inserted_count = $this->bulk_insert_leads($to_insert);
			$results['leads_created'] += $inserted_count;

			// If insert was successful, add to synced records
			if ($inserted_count > 0) {
				$synced_record_ids = array_merge($synced_record_ids, $insert_record_ids);
			}

			// Log creations
			if (class_exists('AQOP_Event_Logger')) {
				foreach ($to_insert as $lead) {
					// Note: We don't have the new IDs here easily without individual inserts or extra queries,
					// so we might log generically or skip ID logging for bulk operations to save performance.
					// For now, let's skip individual logging for bulk inserts to maximize speed.
				}
			}
		}

		// 5. Individual Updates
		foreach ($to_update as $lead) {
			$lead_id = $lead['id'];
			$original_airtable_id = isset($lead['_original_airtable_id']) ? $lead['_original_airtable_id'] : '';
			unset($lead['id']); // Remove ID from update data
			unset($lead['_original_airtable_id']); // Remove internal tracking field

			$lead['updated_at'] = current_time('mysql');

			$update_result = $wpdb->update(
				$wpdb->prefix . 'aq_leads',
				$lead,
				array('id' => $lead_id)
			);

			if ($update_result !== false) {
				$results['leads_updated']++;
				// Add to synced records
				if (!empty($original_airtable_id)) {
					$synced_record_ids[] = $original_airtable_id;
				}
			}
		}

		// 6. Add synced records to the queue for batch marking
		if (!empty($synced_record_ids)) {
			$this->sync_queue = array_merge($this->sync_queue, $synced_record_ids);
		}
	}

	/**
	 * Prepare lead data from Airtable record
	 *
	 * @param array $record Airtable record
	 * @param array &$results Results reference for tracking created entities
	 * @return array|null Prepared lead data or null if invalid
	 */
	private function prepare_lead_data($record, &$results)
	{
		$mapped_data = $this->map_airtable_record($record);

		// DEBUG: Log the full mapped_data to trace issues
		$this->debug && error_log('[AQOP Sync Debug] Record ID: ' . $record['id']);
		$this->debug && error_log('[AQOP Sync Debug] Full mapped_data: ' . print_r($mapped_data, true));
		$this->debug && error_log('[AQOP Sync Debug] Airtable fields: ' . print_r(array_keys($record['fields'] ?? []), true));

		if (empty($mapped_data['lead_name']) && empty($mapped_data['email'])) {
			$this->debug && error_log('[AQOP Sync Debug] Skipping record - no lead_name or email');
			return null; // Skip invalid records
		}

		// Handle related entities (using cache)
		if (!empty($mapped_data['country_name'])) {
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Country value from Airtable: "' . $mapped_data['country_name'] . '"');
			$mapped_data['country_id'] = $this->get_or_create_country($mapped_data['country_name']);
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Received country_id=' . ($mapped_data['country_id'] ?? 'NULL'));
			unset($mapped_data['country_name']);
		}

		if (!empty($mapped_data['group_name'])) {
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Group value from Airtable: "' . $mapped_data['group_name'] . '"');
			$mapped_data['group_id'] = $this->get_or_create_campaign_group($mapped_data['group_name']);
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Received group_id=' . ($mapped_data['group_id'] ?? 'NULL'));
			unset($mapped_data['group_name']);
		}

		if (!empty($mapped_data['campaign_name'])) {
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Campaign value from Airtable: "' . $mapped_data['campaign_name'] . '"');
			$mapped_data['campaign_id'] = $this->get_or_create_campaign(
				$mapped_data['campaign_name'],
				$mapped_data['group_id'] ?? null,
				$mapped_data['country_id'] ?? null,
				$mapped_data['platform'] ?? null
			);
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Received campaign_id=' . ($mapped_data['campaign_id'] ?? 'NULL'));
			unset($mapped_data['campaign_name']);
		}

		if (!empty($mapped_data['source_name'])) {
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Source value from Airtable: "' . $mapped_data['source_name'] . '"');
			$mapped_data['source_id'] = $this->get_or_create_source($mapped_data['source_name']);
			$this->debug && error_log('[AQOP Sync] prepare_lead_data: Received source_id=' . ($mapped_data['source_id'] ?? 'NULL'));
			unset($mapped_data['source_name']);
		}

		// Build lead_data from mapped_data
		$lead_data = array();

		// Transfer basic fields from mapped_data
		$basic_fields = array('name', 'lead_name', 'email', 'phone', 'priority', 'notes', 'platform', 'status_id', 'custom_data');
		foreach ($basic_fields as $field) {
			if (isset($mapped_data[$field])) {
				$lead_data[$field] = $mapped_data[$field];
			}
		}

		// Handle name field: map 'lead_name' to 'name' if 'name' not set
		if (!isset($lead_data['name']) && isset($lead_data['lead_name'])) {
			$lead_data['name'] = $lead_data['lead_name'];
			unset($lead_data['lead_name']);
		}

		// Add relational IDs
		if (isset($mapped_data['country_id'])) {
			$lead_data['country_id'] = $mapped_data['country_id'];
		}
		if (isset($mapped_data['campaign_id'])) {
			$lead_data['campaign_id'] = $mapped_data['campaign_id'];
		}
		if (isset($mapped_data['group_id'])) {
			$lead_data['group_id'] = $mapped_data['group_id'];
		}
		if (isset($mapped_data['source_id'])) {
			$lead_data['source_id'] = $mapped_data['source_id'];
		}

		// Add debug logging
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$this->debug && error_log('[AQOP Sync] Record ID: ' . $record['id']);
			$this->debug && error_log('[AQOP Sync] Mapped name: ' . (isset($mapped_data['lead_name']) ? $mapped_data['lead_name'] : (isset($mapped_data['name']) ? $mapped_data['name'] : 'EMPTY')));
			$this->debug && error_log('[AQOP Sync] Final lead_data name: ' . (isset($lead_data['name']) ? $lead_data['name'] : 'EMPTY'));
		}

		// CRITICAL: Set default priority if not set (database column is NOT NULL)
		if (!isset($lead_data['priority']) || empty($lead_data['priority'])) {
			$lead_data['priority'] = 'medium';
		}

		// Set default status_id if not set
		if (!isset($lead_data['status_id']) || empty($lead_data['status_id'])) {
			$lead_data['status_id'] = 1; // Default to 'pending'
		}

		$lead_data['airtable_record_id'] = $record['id'];

		return $lead_data;
	}

	/**
	 * Get map of existing leads by email and airtable_record_id
	 *
	 * @param array $emails List of emails
	 * @param array $airtable_ids List of Airtable IDs
	 * @return array Map with 'by_email' and 'by_airtable_id'
	 */
	private function get_existing_leads_map($emails, $airtable_ids)
	{
		global $wpdb;

		$map = array(
			'by_email' => array(),
			'by_airtable_id' => array(),
		);

		if (empty($emails) && empty($airtable_ids)) {
			return $map;
		}

		$where_clauses = array();
		$values = array();

		if (!empty($emails)) {
			$emails = array_unique(array_filter($emails));
			if (!empty($emails)) {
				$placeholders = implode(',', array_fill(0, count($emails), '%s'));
				$where_clauses[] = "email IN ($placeholders)";
				$values = array_merge($values, $emails);
			}
		}

		if (!empty($airtable_ids)) {
			$airtable_ids = array_unique(array_filter($airtable_ids));
			if (!empty($airtable_ids)) {
				$placeholders = implode(',', array_fill(0, count($airtable_ids), '%s'));
				$where_clauses[] = "airtable_record_id IN ($placeholders)";
				$values = array_merge($values, $airtable_ids);
			}
		}

		if (empty($where_clauses)) {
			return $map;
		}

		$sql = "SELECT id, email, airtable_record_id FROM {$wpdb->prefix}aq_leads WHERE " . implode(' OR ', $where_clauses);

		$results = $wpdb->get_results($wpdb->prepare($sql, $values));

		foreach ($results as $row) {
			if (!empty($row->email)) {
				$map['by_email'][$row->email] = $row->id;
			}
			if (!empty($row->airtable_record_id)) {
				$map['by_airtable_id'][$row->airtable_record_id] = $row->id;
			}
		}

		return $map;
	}

	/**
	 * Bulk insert leads
	 *
	 * @param array $leads List of lead data arrays
	 * @return int Number of rows inserted
	 */
	private function bulk_insert_leads($leads)
	{
		global $wpdb;

		if (empty($leads)) {
			return 0;
		}

		// Get all possible keys from the first lead (assuming consistent structure from prepare_lead_data)
		// However, some optional fields might be missing in some records.
		// We need a unified set of columns.
		$columns = array(
			'name',
			'email',
			'phone',
			'priority',
			'notes',
			'platform',
			'status_id',
			'custom_data',
			'country_id',
			'campaign_id',
			'group_id',
			'source_id',
			'airtable_record_id',
			'created_at',
			'updated_at'
		);

		$values = array();
		$placeholders = array();
		$current_time = current_time('mysql');

		foreach ($leads as $lead) {
			$row_values = array();
			$row_placeholders = array();

			foreach ($columns as $col) {
				if ($col === 'created_at' || $col === 'updated_at') {
					$val = $current_time;
					$format = '%s';
				} elseif (isset($lead[$col])) {
					$val = $lead[$col];
					// Determine format
					if (in_array($col, array('status_id', 'country_id', 'campaign_id', 'group_id', 'source_id'))) {
						$format = '%d';
					} else {
						$format = '%s';
					}
				} else {
					// Handle NOT NULL columns with defaults
					if ($col === 'priority') {
						$val = 'medium'; // Priority is NOT NULL, default to medium
						$format = '%s';
					} elseif ($col === 'status_id') {
						$val = 1; // Default to 'pending'
						$format = '%d';
					} else {
						$val = null; // Default null for nullable columns
						$format = 'NULL';
					}
				}

				if ($format === 'NULL') {
					$row_placeholders[] = 'NULL';
				} else {
					$values[] = $val;
					$row_placeholders[] = $format;
				}
			}
			$placeholders[] = '(' . implode(', ', $row_placeholders) . ')';
		}

		$sql = "INSERT INTO {$wpdb->prefix}aq_leads (" . implode(', ', $columns) . ") VALUES " . implode(', ', $placeholders);

		// Execute query
		$result = $wpdb->query($wpdb->prepare($sql, $values));

		return $result ? $result : 0;
	}

	/**
	 * Get or create country by name
	 *
	 * @param string $country_name Country name
	 * @return int|null Country ID
	 */
	public function get_or_create_country($country_name)
	{
		global $wpdb;

		if (empty($country_name)) {
			$this->debug && error_log('[AQOP Sync] get_or_create_country: Empty country name, returning null');
			return null;
		}

		$country_name = sanitize_text_field($country_name);
		$cache_key = strtolower($country_name);

		$this->debug && error_log('[AQOP Sync] get_or_create_country: Looking for "' . $country_name . '"');

		// Check cache first
		if (isset($this->countries_cache[$cache_key])) {
			$this->debug && error_log('[AQOP Sync] get_or_create_country: Found in cache, ID=' . $this->countries_cache[$cache_key]);
			return $this->countries_cache[$cache_key];
		}

		// Check if exists - search BOTH English AND Arabic columns
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_dim_countries WHERE country_name_en = %s OR country_name_ar = %s",
				$country_name,
				$country_name
			)
		);

		if ($existing) {
			$this->debug && error_log('[AQOP Sync] get_or_create_country: Found existing, ID=' . $existing);
			$this->countries_cache[$cache_key] = $existing;
			return $existing;
		}

		// Generate country code from initials
		$words = explode(' ', $country_name);
		$code = '';
		foreach ($words as $word) {
			if (!empty($word)) {
				$code .= strtoupper(substr($word, 0, 1));
			}
		}

		// Fallback if code is too short (e.g. single word country like "Egypt")
		if (strlen($code) < 2) {
			$code = strtoupper(substr($country_name, 0, 2));
		}

		// Ensure max 3 chars
		$code = substr($code, 0, 3);
		$base_code = $code;
		$counter = 1;

		// Ensure uniqueness
		while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}aq_dim_countries WHERE country_code = %s", $code))) {
			// If code is 3 chars, truncate to 2 to make room for number
			if (strlen($base_code) === 3) {
				$prefix = substr($base_code, 0, 2);
			} else {
				$prefix = $base_code;
			}

			$code = $prefix . $counter;
			$counter++;

			// Safety break to prevent infinite loop
			if ($counter > 9) {
				// Fallback to random 2 digits
				$code = substr($prefix, 0, 1) . rand(10, 99);
				break;
			}
		}

		// Create new country
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'aq_dim_countries',
			array(
				'country_name_en' => $country_name,
				'country_name_ar' => $country_name, // Same for both as placeholder
				'country_code' => $code,
			),
			array('%s', '%s', '%s')
		);

		if ($inserted) {
			$id = $wpdb->insert_id;
			$this->debug && error_log('[AQOP Sync] get_or_create_country: Created new country, ID=' . $id);
			$this->countries_cache[$cache_key] = $id;
			return $id;
		}

		$this->debug && error_log('[AQOP Sync] get_or_create_country: Failed to create country, DB error: ' . $wpdb->last_error);
		return null;
	}

	/**
	 * Get or create campaign group by name
	 *
	 * @param string $group_name Group name
	 * @return int|null Group ID
	 */
	public function get_or_create_campaign_group($group_name)
	{
		global $wpdb;

		if (empty($group_name)) {
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign_group: Empty group name, returning null');
			return null;
		}

		$group_name = sanitize_text_field($group_name);
		$cache_key = strtolower($group_name);

		$this->debug && error_log('[AQOP Sync] get_or_create_campaign_group: Looking for "' . $group_name . '"');

		// Check cache first
		if (isset($this->groups_cache[$cache_key])) {
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign_group: Found in cache, ID=' . $this->groups_cache[$cache_key]);
			return $this->groups_cache[$cache_key];
		}

		// Check if exists - search BOTH English AND Arabic columns
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_campaign_groups WHERE group_name_en = %s OR group_name_ar = %s",
				$group_name,
				$group_name
			)
		);

		if ($existing) {
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign_group: Found existing, ID=' . $existing);
			$this->groups_cache[$cache_key] = $existing;
			return $existing;
		}

		// Create new group
		$this->debug && error_log('[AQOP Sync] get_or_create_campaign_group: Creating new group "' . $group_name . '"');
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'aq_campaign_groups',
			array(
				'group_name_en' => $group_name,
				'group_name_ar' => $group_name, // Same for both
				'description' => 'Auto-created from Airtable sync',
				'created_at' => current_time('mysql'),
			),
			array('%s', '%s', '%s', '%s')
		);

		if ($inserted) {
			$id = $wpdb->insert_id;
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign_group: Created new group, ID=' . $id);
			$this->groups_cache[$cache_key] = $id;
			return $id;
		}

		$this->debug && error_log('[AQOP Sync] get_or_create_campaign_group: Failed to create group, DB error: ' . $wpdb->last_error);
		return null;
	}

	/**
	 * Get or create campaign by name
	 *
	 * @param string $campaign_name Campaign name
	 * @param int|null $group_id Group ID
	 * @param int|null $country_id Country ID
	 * @param string|null $platform Platform
	 * @return int|null Campaign ID
	 */
	public function get_or_create_campaign($campaign_name, $group_id = null, $country_id = null, $platform = null)
	{
		global $wpdb;

		if (empty($campaign_name)) {
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign: Empty campaign name, returning null');
			return null;
		}

		$campaign_name = sanitize_text_field($campaign_name);
		$cache_key = strtolower($campaign_name);

		$this->debug && error_log('[AQOP Sync] get_or_create_campaign: Looking for "' . $campaign_name . '" (group_id=' . ($group_id ?? 'NULL') . ', country_id=' . ($country_id ?? 'NULL') . ')');

		// Check cache first
		if (isset($this->campaigns_cache[$cache_key])) {
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign: Found in cache, ID=' . $this->campaigns_cache[$cache_key]);
			return $this->campaigns_cache[$cache_key];
		}

		// Check if exists
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_leads_campaigns WHERE name = %s",
				$campaign_name
			)
		);

		if ($existing) {
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign: Found existing, ID=' . $existing);
			$this->campaigns_cache[$cache_key] = $existing;
			return $existing;
		}

		// Create new campaign
		$this->debug && error_log('[AQOP Sync] get_or_create_campaign: Creating new campaign "' . $campaign_name . '"');
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'aq_leads_campaigns',
			array(
				'group_id' => $group_id,
				'country_id' => $country_id,
				'name' => $campaign_name,
				'description' => 'Auto-created from Airtable sync',
				'platform' => $platform,
				'is_active' => 1,
				'created_at' => current_time('mysql'),
			),
			array('%d', '%d', '%s', '%s', '%s', '%d', '%s')
		);

		if ($inserted) {
			$id = $wpdb->insert_id;
			$this->debug && error_log('[AQOP Sync] get_or_create_campaign: Created new campaign, ID=' . $id);
			$this->campaigns_cache[$cache_key] = $id;
			return $id;
		}

		$this->debug && error_log('[AQOP Sync] get_or_create_campaign: Failed to create campaign, DB error: ' . $wpdb->last_error);
		return null;
	}

	/**
	 * Get or create source by name
	 *
	 * @param string $source_name Source name
	 * @param string $source_type Source type
	 * @return int|null Source ID
	 */
	public function get_or_create_source($source_name, $source_type = 'other')
	{
		global $wpdb;

		if (empty($source_name)) {
			$this->debug && error_log('[AQOP Sync] get_or_create_source: Empty source name, returning null');
			return null;
		}

		$source_name = sanitize_text_field($source_name);
		$cache_key = strtolower($source_name);

		$this->debug && error_log('[AQOP Sync] get_or_create_source: Looking for "' . $source_name . '"');

		// Check cache first
		if (isset($this->sources_cache[$cache_key])) {
			$this->debug && error_log('[AQOP Sync] get_or_create_source: Found in cache, ID=' . $this->sources_cache[$cache_key]);
			return $this->sources_cache[$cache_key];
		}

		// Check if exists by name or code
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_leads_sources WHERE source_name = %s OR source_code = %s",
				$source_name,
				sanitize_title($source_name)
			)
		);

		if ($existing) {
			$this->debug && error_log('[AQOP Sync] get_or_create_source: Found existing, ID=' . $existing);
			$this->sources_cache[$cache_key] = $existing;
			return $existing;
		}

		// Create new source
		$this->debug && error_log('[AQOP Sync] get_or_create_source: Creating new source "' . $source_name . '"');
		$source_code = sanitize_title($source_name);
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'aq_leads_sources',
			array(
				'source_name' => $source_name,
				'source_code' => $source_code,
				'source_type' => $source_type,
				'is_active' => 1,
			),
			array('%s', '%s', '%s', '%d')
		);

		if ($inserted) {
			$id = $wpdb->insert_id;
			$this->debug && error_log('[AQOP Sync] get_or_create_source: Created new source, ID=' . $id);
			$this->sources_cache[$cache_key] = $id;
			return $id;
		}

		$this->debug && error_log('[AQOP Sync] get_or_create_source: Failed to create source, DB error: ' . $wpdb->last_error);
		return null;
	}

	/**
	 * Map Airtable record fields to WordPress fields
	 *
	 * @param array $record Airtable record
	 * @return array Mapped data
	 */
	public function map_airtable_record($record)
	{
		$mapped_data = array();
		$mappings = get_option('aqop_airtable_field_mapping', array());

		// Fix: Handle if mappings were stored as JSON string instead of array
		if (is_string($mappings) && !empty($mappings)) {
			$decoded = json_decode($mappings, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				$mappings = $decoded;
				// Also fix the stored value for future calls
				update_option('aqop_airtable_field_mapping', $mappings);
				error_log('AQOP: Fixed field mappings from JSON string to array');
			} else {
				error_log('AQOP: Failed to decode field mappings JSON: ' . json_last_error_msg());
				$mappings = array();
			}
		}

		if (empty($mappings) || !is_array($mappings) || !isset($record['fields'])) {
			$this->debug && error_log('[AQOP Sync Debug] map_airtable_record: Empty mappings or no record fields');
			$this->debug && error_log('[AQOP Sync Debug] mappings count: ' . (is_array($mappings) ? count($mappings) : 'not array'));
			$this->debug && error_log('[AQOP Sync Debug] record has fields: ' . (isset($record['fields']) ? 'yes' : 'no'));
			return $mapped_data;
		}

		// DEBUG: Log mappings configuration
		$this->debug && error_log('[AQOP Sync Debug] Processing ' . count($mappings) . ' field mappings');

		foreach ($mappings as $mapping) {
			$airtable_field = $mapping['airtable_field'];
			$wp_field = $mapping['wp_field'];

			if (isset($record['fields'][$airtable_field])) {
				$value = $record['fields'][$airtable_field];

				// CRITICAL FIX: Handle linked records and lookup fields (arrays)
				// Airtable linked records return: ["rec123abc", "rec456def"] (record IDs)
				// Airtable lookup fields return: ["Oman", "Saudi Arabia"] (actual values)
				// We do NOT apply this to custom_data, as we want to preserve the full array/JSON there
				if ($wp_field !== 'custom_data' && is_array($value)) {
					if (empty($value)) {
						// Empty array, skip this field
						error_log("[AQOP Sync] Field '{$airtable_field}' is an empty array, skipping");
						continue;
					}

					$first_value = $value[0];

					// Check if it looks like a record ID (starts with "rec" and is 17 chars)
					if (is_string($first_value) && preg_match('/^rec[a-zA-Z0-9]{14}$/', $first_value)) {
						// This is a linked record with record IDs, not usable text
						// User should use a Lookup field instead
						error_log("[AQOP Sync] Field '{$airtable_field}' contains linked record IDs (e.g., '{$first_value}'). Use a Lookup field to get the actual text value.");
						continue;
					}

					// It's a lookup field with actual values - use the first value
					$value = is_string($first_value) ? $first_value : (string) $first_value;
					error_log("[AQOP Sync] Field '{$airtable_field}' is a lookup array, using first value: '{$value}'");
				}

				// Handle different field types
				switch ($wp_field) {
					case 'name':
					case 'lead_name':
					case 'email':
					case 'phone':
					case 'notes':
					case 'priority':
						$mapped_data[$wp_field] = sanitize_text_field($value);
						break;

					case 'country_id':
						error_log("[AQOP Sync] Mapping country_id: raw value = " . print_r($value, true));
						$mapped_data['country_name'] = sanitize_text_field($value);
						error_log("[AQOP Sync] Mapped country_name = '{$mapped_data['country_name']}'");
						break;

					case 'campaign_id':
						error_log("[AQOP Sync] Mapping campaign_id: raw value = " . print_r($value, true));
						$mapped_data['campaign_name'] = sanitize_text_field($value);
						error_log("[AQOP Sync] Mapped campaign_name = '{$mapped_data['campaign_name']}'");
						break;

					case 'group_id':
						$mapped_data['group_name'] = sanitize_text_field($value);
						break;

					case 'source_id':
						$mapped_data['source_name'] = sanitize_text_field($value);
						break;

					case 'status_id':
						$mapped_data[$wp_field] = intval($value);
						break;

					case 'custom_data':
						$mapped_data[$wp_field] = wp_json_encode($value);
						break;

					case 'platform':
						$mapped_data[$wp_field] = sanitize_text_field($value);
						break;
				}
			}
		}

		return $mapped_data;
	}

	/**
	 * Get format array for database operations
	 *
	 * @param array $data Data array
	 * @return array Format array
	 */
	private function get_format_array($data)
	{
		$formats = array();
		foreach ($data as $key => $value) {
			switch ($key) {
				case 'country_id':
				case 'campaign_id':
				case 'source_id':
				case 'status_id':
					$formats[] = '%d';
					break;
				case 'custom_data':
					$formats[] = '%s';
					break;
				default:
					$formats[] = '%s';
					break;
			}
		}
		return $formats;
	}

	/**
	 * Get sync statistics
	 *
	 * @return array Sync stats
	 */
	public function get_sync_stats()
	{
		return get_option('aqop_airtable_sync_stats', array());
	}

	/**
	 * Get last sync time
	 *
	 * @return string Last sync time
	 */
	public function get_last_sync_time()
	{
		$last_sync = get_option('aqop_airtable_last_sync', '');
		if (empty($last_sync)) {
			return 'Never';
		}
		return date_i18n('M j, Y H:i', strtotime($last_sync));
	}

	/**
	 * Sync a single chunk from Airtable (for chunked processing to avoid timeout)
	 *
	 * @param string $offset Airtable pagination offset (empty for first chunk)
	 * @param int $page_size Number of records per chunk (default 50)
	 * @param bool $force_full_sync If true, ignores smart sync filter and syncs all records
	 * @return array Chunk results with next offset
	 */
	public function sync_chunk($offset = '', $page_size = 50, $force_full_sync = false)
	{
		// Prefetch related data on first chunk
		if (empty($offset)) {
			$this->prefetch_related_data();
			// Clear previous sync stats on first chunk
			delete_option('aqop_airtable_chunk_stats');
			// Clear sync queue
			$this->sync_queue = array();
		} else {
			$this->prefetch_related_data();
		}

		// Set reasonable limits for chunk processing
		@set_time_limit(60);
		@ini_set('max_execution_time', 60);

		$results = array(
			'success' => false,
			'message' => '',
			'chunk_processed' => 0,
			'chunk_created' => 0,
			'chunk_updated' => 0,
			'chunk_marked' => 0,
			'next_offset' => '',
			'is_complete' => false,
			'errors' => array(),
			'smart_sync_enabled' => false,
		);

		// Get cumulative stats from previous chunks
		$cumulative = get_option('aqop_airtable_chunk_stats', array(
			'leads_processed' => 0,
			'leads_created' => 0,
			'leads_updated' => 0,
			'leads_marked' => 0,
			'countries_created' => 0,
			'campaigns_created' => 0,
			'groups_created' => 0,
			'sources_created' => 0,
		));

		try {
			// Get Airtable credentials
			$api_key = get_option('aqop_airtable_token', '');
			$base_id = get_option('aqop_airtable_base_id', '');
			$table_name = get_option('aqop_airtable_table_name', '');

			if (empty($api_key) || empty($base_id) || empty($table_name)) {
				throw new Exception('Airtable credentials not configured');
			}

			// Check if smart sync is enabled
			$smart_sync_enabled = get_option('aqop_airtable_smart_sync_enabled', false) && !$force_full_sync;
			$results['smart_sync_enabled'] = $smart_sync_enabled;

			// Build the API URL
			$url = self::API_BASE_URL . $base_id . '/' . rawurlencode($table_name) . '?pageSize=' . intval($page_size);

			// Add smart sync filter if enabled
			if ($smart_sync_enabled) {
				// Filter for unsynced records: sync_with_aqop = FALSE or BLANK/empty
				$filter = 'OR({sync_with_aqop} = FALSE(), {sync_with_aqop} = BLANK())';
				$url .= '&filterByFormula=' . rawurlencode($filter);
			}

			if (!empty($offset)) {
				$url .= '&offset=' . $offset;
			}

			$response = wp_remote_get($url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 45,
			));

			if (is_wp_error($response)) {
				throw new Exception('Airtable API Error: ' . $response->get_error_message());
			}

			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);

			if (isset($data['error'])) {
				$msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
				throw new Exception('Airtable API Error: ' . $msg);
			}

			$records = isset($data['records']) ? $data['records'] : array();
			$next_offset = isset($data['offset']) ? $data['offset'] : '';

			$results['chunk_processed'] = count($records);

			// Process this chunk
			if (!empty($records)) {
				$chunk_stats = array(
					'leads_created' => 0,
					'leads_updated' => 0,
				);
				$this->process_batch($records, $chunk_stats);

				$results['chunk_created'] = $chunk_stats['leads_created'];
				$results['chunk_updated'] = $chunk_stats['leads_updated'];

				// Mark synced records in Airtable if smart sync is enabled
				if ($smart_sync_enabled && !empty($this->sync_queue)) {
					$marked = $this->batch_mark_as_synced($this->sync_queue);
					$results['chunk_marked'] = $marked;
					$cumulative['leads_marked'] = (isset($cumulative['leads_marked']) ? $cumulative['leads_marked'] : 0) + $marked;
					// Clear the queue after marking
					$this->sync_queue = array();
				}
			}

			// Update cumulative stats
			$cumulative['leads_processed'] += $results['chunk_processed'];
			$cumulative['leads_created'] += $results['chunk_created'];
			$cumulative['leads_updated'] += $results['chunk_updated'];

			// Save cumulative stats
			update_option('aqop_airtable_chunk_stats', $cumulative);

			// Set results
			$results['success'] = true;
			$results['next_offset'] = $next_offset;
			$results['is_complete'] = empty($next_offset);
			$results['cumulative'] = $cumulative;

			// If complete, finalize
			if ($results['is_complete']) {
				update_option('aqop_airtable_last_sync', current_time('mysql'));
				update_option('aqop_airtable_sync_stats', array_merge($cumulative, array(
					'success' => true,
					'message' => sprintf(
						'Sync completed: %d leads processed, %d created, %d updated',
						$cumulative['leads_processed'],
						$cumulative['leads_created'],
						$cumulative['leads_updated']
					),
				)));
				delete_option('aqop_airtable_chunk_stats');

				$results['message'] = sprintf(
					'Sync completed: %d leads processed, %d created, %d updated',
					$cumulative['leads_processed'],
					$cumulative['leads_created'],
					$cumulative['leads_updated']
				);
			} else {
				$results['message'] = sprintf(
					'Processed %d records (total: %d). Continuing...',
					$results['chunk_processed'],
					$cumulative['leads_processed']
				);
			}

		} catch (Exception $e) {
			$results['message'] = 'Chunk sync failed: ' . $e->getMessage();
			$results['errors'][] = $e->getMessage();
		}

		return $results;
	}

	/**
	 * Fetch field metadata from Airtable
	 *
	 * @param string $base_id Base ID
	 * @param string $table_name Table name
	 * @param string $api_key API key
	 * @return array Array of field information
	 */
	public static function fetch_airtable_fields($base_id, $table_name, $api_key)
	{
		if (empty($base_id) || empty($table_name) || empty($api_key)) {
			throw new Exception('Base ID, table name, and API key are required');
		}

		$url = self::API_BASE_URL . 'meta/bases/' . $base_id . '/tables';

		$response = wp_remote_get($url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout' => 60, // Increase from 30 to 60 seconds
		));

		if (is_wp_error($response)) {
			throw new Exception('API request failed: ' . $response->get_error_message());
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception('Invalid JSON response from Airtable');
		}

		// Find the requested table
		$table_fields = array();
		foreach ($data['tables'] ?? array() as $table) {
			if ($table['name'] === $table_name || $table['id'] === $table_name) {
				foreach ($table['fields'] ?? array() as $field) {
					$table_fields[] = array(
						'id' => $field['id'],
						'name' => $field['name'],
						'type' => $field['type'],
					);
				}
				break;
			}
		}

		if (empty($table_fields)) {
			throw new Exception('Table not found or no fields available');
		}

		return $table_fields;
	}
}

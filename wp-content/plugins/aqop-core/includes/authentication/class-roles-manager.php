<?php
/**
 * Roles Manager Class
 *
 * Manages custom WordPress roles for Operation Platform.
 * Creates and removes operation-specific roles with appropriate capabilities.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Roles_Manager class.
 *
 * Handles creation and removal of Operation Platform roles.
 * Defines capabilities for different user levels.
 *
 * @since 1.0.0
 */
class AQOP_Roles_Manager
{

	/**
	 * Create custom roles.
	 *
	 * Creates all Operation Platform roles with appropriate capabilities:
	 * - operation_admin: Full administrative access
	 * - operation_manager: Limited management access
	 * - aq_supervisor: Team supervision capabilities
	 * - aq_agent: Agent-level access
	 *
	 * Should be called on plugin activation.
	 *
	 * @since  1.0.0
	 * @static
	 * @return array List of created roles with their status.
	 */
	public static function create_roles()
	{
		$roles_created = array();

		// Get administrator capabilities as base for operation_admin.
		$admin_role = get_role('administrator');
		$admin_capabilities = $admin_role ? $admin_role->capabilities : array();

		/**
		 * Role 1: Operation Admin
		 *
		 * Full administrative access to Operation Platform.
		 * Inherits all WordPress admin capabilities plus custom operation capabilities.
		 */
		$operation_admin_caps = array_merge(
			$admin_capabilities,
			array(
				'operation_admin' => true,
				'view_control_center' => true,
				'manage_operation' => true,
				'manage_notification_rules' => true,
				'view_event_logs' => true,
				'export_analytics' => true,
				'manage_integrations' => true,
			)
		);

		$admin_role_created = add_role(
			'operation_admin',
			__('Operation Admin', 'aqop-core'),
			$operation_admin_caps
		);

		$roles_created['operation_admin'] = (null !== $admin_role_created);

		/**
		 * Role 2: Operation Manager
		 *
		 * Limited access to Operation Platform.
		 * Can view control center and event logs, export data, but cannot manage.
		 */
		$operation_manager_caps = array(
			'read' => true,
			'view_control_center' => true,
			'view_event_logs' => true,
			'export_analytics' => true,
		);

		$manager_role_created = add_role(
			'operation_manager',
			__('Operation Manager', 'aqop-core'),
			$operation_manager_caps
		);

		$roles_created['operation_manager'] = (null !== $manager_role_created);

		/**
		 * Role 3: Country Manager
		 *
		 * Manages leads and agents within a specific country.
		 * Can view/edit leads in their country, assign to agents, view reports.
		 */
		$country_manager_caps = array(
			'read' => true,
			'view_control_center' => true,
			'view_event_logs' => true,
			'manage_team_leads' => true,
			'assign_leads' => true,
			'view_team_reports' => true,
			'edit_own_leads' => true,
			'view_own_leads' => true,
			'add_lead_notes' => true,
			'export_analytics' => true,
			// Custom cap to identify this role type
			'manage_country_leads' => true,
		);

		$country_manager_role_created = add_role(
			'aq_country_manager',
			__('Country Manager', 'aqop-core'),
			$country_manager_caps
		);

		$roles_created['aq_country_manager'] = (null !== $country_manager_role_created);

		/**
		 * Role 4: Digital Marketing
		 *
		 * Manages Facebook Lead Ads integration and campaigns.
		 * Can view leads but has limited management capabilities.
		 */
		$digital_marketing_caps = array(
			'read' => true,
			'manage_facebook_leads' => true,
			'view_leads' => true,
			'edit_leads' => false,
			'delete_leads' => false,
			'export_leads' => false,
			'manage_campaigns' => true,
			'manage_settings' => false,
		);

		$marketing_role_created = add_role(
			'digital_marketing',
			__('Digital Marketing', 'aqop-core'),
			$digital_marketing_caps
		);

		$roles_created['digital_marketing'] = (null !== $marketing_role_created);

		/**
		 * Role 3: AQ Supervisor
		 *
		 * Team supervision capabilities.
		 * Can manage team members, view reports, assign leads.
		 */
		$aq_supervisor_caps = array(
			'read' => true,
			'view_control_center' => true,
			'view_event_logs' => true,
			'manage_team_leads' => true,
			'assign_leads' => true,
			'view_team_reports' => true,
			'edit_own_leads' => true,
			'view_own_leads' => true,
			'add_lead_notes' => true,
			'export_analytics' => true,
		);

		$supervisor_role_created = add_role(
			'aq_supervisor',
			__('AQ Supervisor', 'aqop-core'),
			$aq_supervisor_caps
		);

		$roles_created['aq_supervisor'] = (null !== $supervisor_role_created);

		/**
		 * Role 4: AQ Agent
		 *
		 * Agent-level access.
		 * Can view and manage assigned leads, add notes.
		 */
		$aq_agent_caps = array(
			'read' => true,
			'edit_own_leads' => true,
			'view_own_leads' => true,
			'add_lead_notes' => true,
			'update_lead_status' => true,
		);

		$agent_role_created = add_role(
			'aq_agent',
			__('AQ Agent', 'aqop-core'),
			$aq_agent_caps
		);

		$roles_created['aq_agent'] = (null !== $agent_role_created);

		/**
		 * Fires after Operation Platform roles have been created.
		 *
		 * @since 1.0.0
		 *
		 * @param array $roles_created Array of role names and their creation status.
		 */
		do_action('aqop_roles_created', $roles_created);

		// Log role creation event.
		if (class_exists('AQOP_Event_Logger')) {
			AQOP_Event_Logger::log(
				'core',
				'roles_created',
				'system',
				0,
				array(
					'roles_created' => $roles_created,
					'timestamp' => current_time('mysql'),
				)
			);
		}

		return $roles_created;
	}

	/**
	 * Remove custom roles.
	 *
	 * Removes all Operation Platform custom roles.
	 * Should be called on plugin deactivation (optional) or uninstall.
	 *
	 * @since  1.0.0
	 * @static
	 * @return array List of removed roles with their status.
	 */
	public static function remove_roles()
	{
		$roles_removed = array();

		// Remove operation_admin role.
		remove_role('operation_admin');
		$roles_removed['operation_admin'] = !get_role('operation_admin');

		// Remove operation_manager role.
		remove_role('operation_manager');
		$roles_removed['operation_manager'] = !get_role('operation_manager');

		// Remove digital_marketing role.
		remove_role('digital_marketing');
		$roles_removed['digital_marketing'] = !get_role('digital_marketing');

		// Remove aq_supervisor role.
		remove_role('aq_supervisor');
		$roles_removed['aq_supervisor'] = !get_role('aq_supervisor');

		// Remove aq_agent role.
		remove_role('aq_agent');
		$roles_removed['aq_agent'] = !get_role('aq_agent');

		/**
		 * Fires after Operation Platform roles have been removed.
		 *
		 * @since 1.0.0
		 *
		 * @param array $roles_removed Array of role names and their removal status.
		 */
		do_action('aqop_roles_removed', $roles_removed);

		// Log role removal event.
		if (class_exists('AQOP_Event_Logger')) {
			AQOP_Event_Logger::log(
				'core',
				'roles_removed',
				'system',
				0,
				array(
					'roles_removed' => $roles_removed,
					'timestamp' => current_time('mysql'),
				)
			);
		}

		return $roles_removed;
	}

	/**
	 * Get all Operation Platform roles.
	 *
	 * Returns an array of all custom operation roles.
	 *
	 * @since  1.0.0
	 * @static
	 * @return array Array of role slugs.
	 */
	public static function get_operation_roles()
	{
		return array(
			'operation_admin',
			'operation_manager',
			'aq_country_manager',
			'digital_marketing',
			'aq_supervisor',
			'aq_agent',
		);
	}

	/**
	 * Get role display name.
	 *
	 * Returns the translated display name for a role.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $role_slug Role slug.
	 * @return string Role display name or empty string if not found.
	 */
	public static function get_role_display_name($role_slug)
	{
		$role_obj = get_role($role_slug);

		if (!$role_obj) {
			return '';
		}

		$wp_roles = wp_roles();
		$role_names = $wp_roles->role_names;

		return isset($role_names[$role_slug]) ? translate_user_role($role_names[$role_slug]) : '';
	}

	/**
	 * Check if a role exists.
	 *
	 * Checks if a specific role exists in WordPress.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $role_slug Role slug to check.
	 * @return bool True if role exists, false otherwise.
	 */
	public static function role_exists($role_slug)
	{
		return null !== get_role($role_slug);
	}

	/**
	 * Add capability to role.
	 *
	 * Adds a specific capability to an existing role.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $role_slug  Role slug.
	 * @param  string $capability Capability to add.
	 * @return bool True on success, false on failure.
	 */
	public static function add_capability_to_role($role_slug, $capability)
	{
		$role = get_role($role_slug);

		if (!$role) {
			return false;
		}

		$role->add_cap($capability);
		return true;
	}

	/**
	 * Remove capability from role.
	 *
	 * Removes a specific capability from an existing role.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $role_slug  Role slug.
	 * @param  string $capability Capability to remove.
	 * @return bool True on success, false on failure.
	 */
	public static function remove_capability_from_role($role_slug, $capability)
	{
		$role = get_role($role_slug);

		if (!$role) {
			return false;
		}

		$role->remove_cap($capability);
		return true;
	}
}


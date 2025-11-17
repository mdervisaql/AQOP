<?php
/**
 * API Documentation Page
 *
 * Displays comprehensive REST API documentation for developers.
 *
 * @package AQOP_Leads
 * @since   1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Check permissions
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'aqop-leads' ) );
}

$api_base = rest_url( 'aqop/v1' );
?>

<div class="wrap aqop-api-docs">
	<h1>
		<span class="dashicons dashicons-rest-api"></span>
		<?php esc_html_e( 'REST API Documentation', 'aqop-leads' ); ?>
	</h1>
	
	<p class="description">
		<?php esc_html_e( 'Complete API reference for integrating external applications with the Leads Module.', 'aqop-leads' ); ?>
	</p>
	
	<div class="card">
		<h2><?php esc_html_e( 'Base URL', 'aqop-leads' ); ?></h2>
		<p>
			<code class="api-url"><?php echo esc_url( $api_base ); ?></code>
			<button type="button" class="button button-small copy-btn" data-clipboard="<?php echo esc_attr( $api_base ); ?>">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', 'aqop-leads' ); ?>
			</button>
		</p>
	</div>
	
	<div class="card">
		<h2><?php esc_html_e( 'Authentication', 'aqop-leads' ); ?></h2>
		<p><?php esc_html_e( 'All endpoints require authentication. You can use:', 'aqop-leads' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Cookie Authentication', 'aqop-leads' ); ?></strong> - <?php esc_html_e( 'For same-domain requests', 'aqop-leads' ); ?></li>
			<li><strong><?php esc_html_e( 'Application Passwords', 'aqop-leads' ); ?></strong> - <?php esc_html_e( 'Recommended for external apps (WordPress 5.6+)', 'aqop-leads' ); ?></li>
			<li><strong><?php esc_html_e( 'OAuth', 'aqop-leads' ); ?></strong> - <?php esc_html_e( 'For third-party integrations', 'aqop-leads' ); ?></li>
		</ul>
		<p>
			<a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>" class="button">
				<?php esc_html_e( 'Manage Application Passwords', 'aqop-leads' ); ?>
			</a>
		</p>
	</div>
	
	<!-- Endpoints Documentation -->
	<div class="api-endpoints">
		
		<!-- List Leads -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method get">GET</span>
				<?php esc_html_e( 'List Leads', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>GET <?php echo esc_url( $api_base . '/leads' ); ?></code></p>
			
			<h4><?php esc_html_e( 'Query Parameters', 'aqop-leads' ); ?></h4>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Parameter', 'aqop-leads' ); ?></th>
						<th><?php esc_html_e( 'Type', 'aqop-leads' ); ?></th>
						<th><?php esc_html_e( 'Description', 'aqop-leads' ); ?></th>
						<th><?php esc_html_e( 'Default', 'aqop-leads' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>page</code></td>
						<td>integer</td>
						<td><?php esc_html_e( 'Page number for pagination', 'aqop-leads' ); ?></td>
						<td>1</td>
					</tr>
					<tr>
						<td><code>per_page</code></td>
						<td>integer</td>
						<td><?php esc_html_e( 'Number of items per page (max: 200)', 'aqop-leads' ); ?></td>
						<td>50</td>
					</tr>
					<tr>
						<td><code>search</code></td>
						<td>string</td>
						<td><?php esc_html_e( 'Search by name, email, phone', 'aqop-leads' ); ?></td>
						<td>-</td>
					</tr>
					<tr>
						<td><code>status</code></td>
						<td>string</td>
						<td><?php esc_html_e( 'Filter by status code (pending, contacted, etc.)', 'aqop-leads' ); ?></td>
						<td>-</td>
					</tr>
					<tr>
						<td><code>country</code></td>
						<td>integer</td>
						<td><?php esc_html_e( 'Filter by country ID', 'aqop-leads' ); ?></td>
						<td>-</td>
					</tr>
					<tr>
						<td><code>source</code></td>
						<td>integer</td>
						<td><?php esc_html_e( 'Filter by source ID', 'aqop-leads' ); ?></td>
						<td>-</td>
					</tr>
					<tr>
						<td><code>priority</code></td>
						<td>string</td>
						<td><?php esc_html_e( 'Filter by priority (urgent, high, medium, low)', 'aqop-leads' ); ?></td>
						<td>-</td>
					</tr>
					<tr>
						<td><code>orderby</code></td>
						<td>string</td>
						<td><?php esc_html_e( 'Sort by field (id, name, email, created_at, etc.)', 'aqop-leads' ); ?></td>
						<td>created_at</td>
					</tr>
					<tr>
						<td><code>order</code></td>
						<td>string</td>
						<td><?php esc_html_e( 'Sort direction (ASC, DESC)', 'aqop-leads' ); ?></td>
						<td>DESC</td>
					</tr>
				</tbody>
			</table>
			
			<h4><?php esc_html_e( 'Example Request', 'aqop-leads' ); ?></h4>
			<pre class="code-block">GET <?php echo esc_url( $api_base . '/leads?status=pending&per_page=20&orderby=created_at&order=DESC' ); ?></pre>
			
			<h4><?php esc_html_e( 'Example Response', 'aqop-leads' ); ?></h4>
			<pre class="code-block">{
  "leads": [
    {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+966 50 123 4567",
      "status_name_en": "Pending",
      "country_name_en": "Saudi Arabia",
      "created_at": "2024-11-17 14:30:00"
    }
  ],
  "total": 150,
  "pages": 8,
  "page": 1,
  "per_page": 20
}</pre>
		</div>
		
		<!-- Get Single Lead -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method get">GET</span>
				<?php esc_html_e( 'Get Single Lead', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>GET <?php echo esc_url( $api_base . '/leads/{id}' ); ?></code></p>
			
			<h4><?php esc_html_e( 'Example Request', 'aqop-leads' ); ?></h4>
			<pre class="code-block">GET <?php echo esc_url( $api_base . '/leads/123' ); ?></pre>
			
			<h4><?php esc_html_e( 'Response', 'aqop-leads' ); ?></h4>
			<pre class="code-block">{
  "lead": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+966 50 123 4567",
    "whatsapp": "+966 50 123 4567",
    "country_id": 1,
    "source_id": 2,
    "status_id": 1,
    "priority": "high",
    "created_at": "2024-11-17 14:30:00"
  },
  "notes": [...]
}</pre>
		</div>
		
		<!-- Create Lead -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method post">POST</span>
				<?php esc_html_e( 'Create Lead', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>POST <?php echo esc_url( $api_base . '/leads' ); ?></code></p>
			
			<h4><?php esc_html_e( 'Request Body (JSON)', 'aqop-leads' ); ?></h4>
			<pre class="code-block">{
  "name": "Ahmed Ali",
  "email": "ahmed@example.com",
  "phone": "+966 50 999 8888",
  "whatsapp": "+966 50 999 8888",
  "country_id": 1,
  "source_id": 3,
  "priority": "high",
  "status": "pending",
  "note": "Initial contact via website form"
}</pre>
			
			<h4><?php esc_html_e( 'Required Fields', 'aqop-leads' ); ?></h4>
			<ul>
				<li><code>name</code> - <?php esc_html_e( 'Lead full name', 'aqop-leads' ); ?></li>
				<li><code>email</code> - <?php esc_html_e( 'Valid email address', 'aqop-leads' ); ?></li>
				<li><code>phone</code> - <?php esc_html_e( 'Phone number', 'aqop-leads' ); ?></li>
			</ul>
			
			<h4><?php esc_html_e( 'Response (201 Created)', 'aqop-leads' ); ?></h4>
			<pre class="code-block">{
  "message": "Lead created successfully.",
  "lead": {
    "id": 456,
    "name": "Ahmed Ali",
    ...
  }
}</pre>
		</div>
		
		<!-- Update Lead -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method put">PUT</span>
				<?php esc_html_e( 'Update Lead', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>PUT <?php echo esc_url( $api_base . '/leads/{id}' ); ?></code></p>
			
			<h4><?php esc_html_e( 'Request Body (JSON)', 'aqop-leads' ); ?></h4>
			<pre class="code-block">{
  "status": "contacted",
  "priority": "urgent",
  "assigned_to": 5
}</pre>
			
			<p class="description"><?php esc_html_e( 'Only include fields you want to update. All other fields remain unchanged.', 'aqop-leads' ); ?></p>
		</div>
		
		<!-- Delete Lead -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method delete">DELETE</span>
				<?php esc_html_e( 'Delete Lead', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>DELETE <?php echo esc_url( $api_base . '/leads/{id}' ); ?></code></p>
			
			<h4><?php esc_html_e( 'Response', 'aqop-leads' ); ?></h4>
			<pre class="code-block">{
  "message": "Lead deleted successfully.",
  "deleted": true,
  "id": 123
}</pre>
		</div>
		
		<!-- Statuses Endpoint -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method get">GET</span>
				<?php esc_html_e( 'Get Available Statuses', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>GET <?php echo esc_url( $api_base . '/leads/statuses' ); ?></code></p>
			<p class="description"><?php esc_html_e( 'Public endpoint - no authentication required.', 'aqop-leads' ); ?></p>
		</div>
		
		<!-- Countries Endpoint -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method get">GET</span>
				<?php esc_html_e( 'Get Available Countries', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>GET <?php echo esc_url( $api_base . '/leads/countries' ); ?></code></p>
			<p class="description"><?php esc_html_e( 'Public endpoint - no authentication required.', 'aqop-leads' ); ?></p>
		</div>
		
		<!-- Sources Endpoint -->
		<div class="card api-endpoint">
			<h3>
				<span class="http-method get">GET</span>
				<?php esc_html_e( 'Get Available Sources', 'aqop-leads' ); ?>
			</h3>
			<p class="endpoint-url"><code>GET <?php echo esc_url( $api_base . '/leads/sources' ); ?></code></p>
			<p class="description"><?php esc_html_e( 'Public endpoint - no authentication required.', 'aqop-leads' ); ?></p>
		</div>
		
	</div>
	
	<!-- cURL Examples -->
	<div class="card">
		<h2><?php esc_html_e( 'cURL Examples', 'aqop-leads' ); ?></h2>
		
		<h3><?php esc_html_e( 'List Leads', 'aqop-leads' ); ?></h3>
		<pre class="code-block">curl -X GET '<?php echo esc_url( $api_base . '/leads?per_page=10' ); ?>' \
  -u username:application_password</pre>
		
		<h3><?php esc_html_e( 'Create Lead', 'aqop-leads' ); ?></h3>
		<pre class="code-block">curl -X POST '<?php echo esc_url( $api_base . '/leads' ); ?>' \
  -u username:application_password \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Ahmed Ali",
    "email": "ahmed@example.com",
    "phone": "+966501234567",
    "country_id": 1,
    "priority": "high"
  }'</pre>
		
		<h3><?php esc_html_e( 'Update Lead Status', 'aqop-leads' ); ?></h3>
		<pre class="code-block">curl -X PUT '<?php echo esc_url( $api_base . '/leads/123' ); ?>' \
  -u username:application_password \
  -H 'Content-Type: application/json' \
  -d '{
    "status": "contacted"
  }'</pre>
	</div>
	
	<!-- Error Responses -->
	<div class="card">
		<h2><?php esc_html_e( 'Error Responses', 'aqop-leads' ); ?></h2>
		
		<h4>400 Bad Request</h4>
		<pre class="code-block">{
  "code": "missing_required_fields",
  "message": "Name, email, and phone are required fields.",
  "data": { "status": 400 }
}</pre>
		
		<h4>403 Forbidden</h4>
		<pre class="code-block">{
  "code": "rest_forbidden",
  "message": "You do not have permission to access this endpoint.",
  "data": { "status": 403 }
}</pre>
		
		<h4>404 Not Found</h4>
		<pre class="code-block">{
  "code": "lead_not_found",
  "message": "Lead not found.",
  "data": { "status": 404 }
}</pre>
	</div>
	
	<!-- Rate Limiting -->
	<div class="card">
		<h2><?php esc_html_e( 'Rate Limiting', 'aqop-leads' ); ?></h2>
		<p><?php esc_html_e( 'API requests are subject to rate limiting:', 'aqop-leads' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Maximum 60 requests per minute per user', 'aqop-leads' ); ?></li>
			<li><?php esc_html_e( 'Maximum 1000 requests per hour per user', 'aqop-leads' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Rate limit information is returned in response headers:', 'aqop-leads' ); ?></p>
		<pre class="code-block">X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1638000000</pre>
	</div>
	
</div>

<style>
.aqop-api-docs {
	max-width: 1200px;
}

.aqop-api-docs h1 {
	display: flex;
	align-items: center;
	gap: 10px;
}

.aqop-api-docs .card {
	margin-bottom: 20px;
}

.api-url {
	background: #f6f7f7;
	padding: 8px 12px;
	border-radius: 4px;
	font-size: 14px;
	color: #2271b1;
	border: 1px solid #c3c4c7;
	display: inline-block;
}

.copy-btn {
	vertical-align: middle;
	margin-left: 10px;
}

.http-method {
	display: inline-block;
	padding: 4px 12px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 700;
	color: white;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	margin-right: 10px;
}

.http-method.get {
	background: #48bb78;
}

.http-method.post {
	background: #4299e1;
}

.http-method.put {
	background: #ed8936;
}

.http-method.delete {
	background: #f56565;
}

.endpoint-url {
	margin: 10px 0;
}

.endpoint-url code {
	font-size: 14px;
	background: #2c3338;
	color: #48bb78;
	padding: 10px 15px;
	border-radius: 4px;
	display: inline-block;
}

.code-block {
	background: #2c3338;
	color: #a8dadc;
	padding: 15px;
	border-radius: 4px;
	overflow-x: auto;
	font-size: 13px;
	line-height: 1.6;
	border: 1px solid #1d2327;
}

.api-endpoint h3 {
	display: flex;
	align-items: center;
	margin-top: 0;
}

.api-endpoint h4 {
	margin-top: 20px;
	margin-bottom: 10px;
	color: #1d2327;
	font-size: 14px;
	font-weight: 600;
}

.api-endpoint table {
	margin-top: 10px;
}

.api-endpoint table th {
	background: #f6f7f7;
	font-weight: 600;
}

.api-endpoint table code {
	background: #f0f6fc;
	padding: 2px 6px;
	border-radius: 2px;
	font-size: 12px;
	color: #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Copy to clipboard
	$('.copy-btn').on('click', function() {
		var text = $(this).data('clipboard');
		var $button = $(this);
		
		navigator.clipboard.writeText(text).then(function() {
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js( __( 'Copied!', 'aqop-leads' ) ); ?>');
			
			setTimeout(function() {
				$button.html(originalText);
			}, 2000);
		});
	});
});
</script>


<?php
/**
 * WebinarJam Logger
 *
 * Handles logging and debug interface.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Logger class.
 *
 * Provides logging functionality and admin log viewer.
 *
 * @since 1.0.0
 */
class WebinarJam_Logger {

	/**
	 * Log table name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'webinarjam_logs';
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_hooks() {
		// Add admin page.
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_ma_clear_webinar_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_ma_export_webinar_logs', array( $this, 'ajax_export_logs' ) );
	}

	/**
	 * Create log table.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			log_type varchar(50) NOT NULL,
			message text NOT NULL,
			context longtext,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY log_type (log_type),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log a message.
	 *
	 * @since 1.0.0
	 * @param string $type Log type (sync, import, attendance, status, error, api).
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return bool Success status.
	 */
	public function log( $type, $message, $context = array() ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'log_type'   => sanitize_key( $type ),
				'message'    => sanitize_text_field( $message ),
				'context'    => wp_json_encode( $context ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		// Also log to WordPress debug log if enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( '[WebinarJam][%s] %s', strtoupper( $type ), $message ) );
		}

		return false !== $result;
	}

	/**
	 * Get logs.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array Array of log entries.
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'type'   => '',
			'limit'  => 100,
			'offset' => 0,
			'order'  => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = '1=1';
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( ' AND log_type = %s', $args['type'] );
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
			WHERE {$where} 
			ORDER BY created_at {$args['order']} 
			LIMIT %d OFFSET %d",
			$args['limit'],
			$args['offset']
		);

		$results = $wpdb->get_results( $sql );

		// Decode context JSON.
		foreach ( $results as $result ) {
			$result->context = json_decode( $result->context, true );
		}

		return $results;
	}

	/**
	 * Get log count.
	 *
	 * @since 1.0.0
	 * @param string $type Optional log type filter.
	 * @return int Log count.
	 */
	public function get_log_count( $type = '' ) {
		global $wpdb;

		if ( $type ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} WHERE log_type = %s",
					$type
				)
			);
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
	}

	/**
	 * Clear logs.
	 *
	 * @since 1.0.0
	 * @param string $type Optional log type to clear.
	 * @return bool Success status.
	 */
	public function clear_logs( $type = '' ) {
		global $wpdb;

		if ( $type ) {
			$result = $wpdb->delete(
				$this->table_name,
				array( 'log_type' => $type ),
				array( '%s' )
			);
		} else {
			$result = $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );
		}

		return false !== $result;
	}

	/**
	 * Add admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_page() {
		add_submenu_page(
			'ma-plugin-options',
			__( 'WebinarJam Logs', 'ma-plugin' ),
			__( 'WebinarJam Logs', 'ma-plugin' ),
			'manage_options',
			'ma-webinarjam-logs',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_admin_page() {
		$current_type = isset( $_GET['log_type'] ) ? sanitize_key( $_GET['log_type'] ) : '';
		$paged        = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page     = 50;
		$offset       = ( $paged - 1 ) * $per_page;

		$logs        = $this->get_logs( array(
			'type'   => $current_type,
			'limit'  => $per_page,
			'offset' => $offset,
		) );
		$total_logs  = $this->get_log_count( $current_type );
		$total_pages = ceil( $total_logs / $per_page );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WebinarJam Logs', 'ma-plugin' ); ?></h1>

			<div class="tablenav top">
				<div class="alignleft actions">
					<select name="log_type" id="log-type-filter">
						<option value=""><?php esc_html_e( 'All Log Types', 'ma-plugin' ); ?></option>
						<option value="sync" <?php selected( $current_type, 'sync' ); ?>><?php esc_html_e( 'Sync', 'ma-plugin' ); ?></option>
						<option value="import" <?php selected( $current_type, 'import' ); ?>><?php esc_html_e( 'Import', 'ma-plugin' ); ?></option>
						<option value="attendance" <?php selected( $current_type, 'attendance' ); ?>><?php esc_html_e( 'Attendance', 'ma-plugin' ); ?></option>
						<option value="status" <?php selected( $current_type, 'status' ); ?>><?php esc_html_e( 'Status', 'ma-plugin' ); ?></option>
						<option value="error" <?php selected( $current_type, 'error' ); ?>><?php esc_html_e( 'Error', 'ma-plugin' ); ?></option>
						<option value="api" <?php selected( $current_type, 'api' ); ?>><?php esc_html_e( 'API', 'ma-plugin' ); ?></option>
					</select>

					<button type="button" id="filter-logs" class="button"><?php esc_html_e( 'Filter', 'ma-plugin' ); ?></button>
				</div>

				<div class="alignright actions">
					<button type="button" id="export-logs" class="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ma_webinarjam_logs' ) ); ?>">
						<?php esc_html_e( 'Export CSV', 'ma-plugin' ); ?>
					</button>
					<button type="button" id="clear-logs" class="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ma_webinarjam_logs' ) ); ?>">
						<?php esc_html_e( 'Clear Logs', 'ma-plugin' ); ?>
					</button>
				</div>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 10%;"><?php esc_html_e( 'Type', 'ma-plugin' ); ?></th>
						<th style="width: 50%;"><?php esc_html_e( 'Message', 'ma-plugin' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Context', 'ma-plugin' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Date', 'ma-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $logs ) ) : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td>
									<?php $this->render_log_type_badge( $log->log_type ); ?>
								</td>
								<td><?php echo esc_html( $log->message ); ?></td>
								<td>
									<?php if ( ! empty( $log->context ) ) : ?>
										<details>
											<summary style="cursor: pointer;"><?php esc_html_e( 'View Context', 'ma-plugin' ); ?></summary>
											<pre style="margin-top: 10px; overflow-x: auto;"><?php echo esc_html( print_r( $log->context, true ) ); ?></pre>
										</details>
									<?php else : ?>
										<span style="color: #999;">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php
									$timestamp = strtotime( $log->created_at );
									echo '<strong>' . esc_html( wp_date( 'M j, Y', $timestamp ) ) . '</strong><br>';
									echo '<small>' . esc_html( wp_date( 'g:i:s A', $timestamp ) ) . '</small>';
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="4" style="text-align: center; padding: 30px;">
								<?php esc_html_e( 'No logs found.', 'ma-plugin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => $total_pages,
							'current'   => $paged,
						) );
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Filter logs.
			$('#filter-logs').on('click', function() {
				var logType = $('#log-type-filter').val();
				var url = new URL(window.location.href);
				
				if (logType) {
					url.searchParams.set('log_type', logType);
				} else {
					url.searchParams.delete('log_type');
				}
				
				url.searchParams.delete('paged');
				window.location.href = url.toString();
			});

			// Export logs.
			$('#export-logs').on('click', function() {
				var nonce = $(this).data('nonce');
				var logType = $('#log-type-filter').val();
				
				var url = ajaxurl + '?action=ma_export_webinar_logs&nonce=' + nonce;
				if (logType) {
					url += '&log_type=' + logType;
				}
				
				window.location.href = url;
			});

			// Clear logs.
			$('#clear-logs').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs? This cannot be undone.', 'ma-plugin' ) ); ?>')) {
					return;
				}

				var $button = $(this);
				var nonce = $button.data('nonce');
				var logType = $('#log-type-filter').val();

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Clearing...', 'ma-plugin' ) ); ?>');

				$.post(ajaxurl, {
					action: 'ma_clear_webinar_logs',
					nonce: nonce,
					log_type: logType
				}, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert('<?php echo esc_js( __( 'Failed to clear logs.', 'ma-plugin' ) ); ?>');
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Clear Logs', 'ma-plugin' ) ); ?>');
					}
				});
			});
		});
		</script>

		<style>
			.wrap details {
				background: #f9f9f9;
				padding: 10px;
				border-radius: 4px;
			}
			.wrap details pre {
				background: white;
				padding: 10px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 12px;
			}
		</style>
		<?php
	}

	/**
	 * Render log type badge.
	 *
	 * @since 1.0.0
	 * @param string $type Log type.
	 * @return void
	 */
	private function render_log_type_badge( $type ) {
		$colors = array(
			'sync'       => '#2271b1',
			'import'     => '#00a32a',
			'attendance' => '#8c8f94',
			'status'     => '#8c8f94',
			'error'      => '#d63638',
			'api'        => '#8c8f94',
		);

		$color = isset( $colors[ $type ] ) ? $colors[ $type ] : '#999';

		printf(
			'<span style="display: inline-block; padding: 3px 8px; background: %s; color: white; border-radius: 3px; font-size: 11px; font-weight: 600;">%s</span>',
			esc_attr( $color ),
			esc_html( strtoupper( $type ) )
		);
	}

	/**
	 * AJAX handler for clearing logs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_clear_logs() {
		check_ajax_referer( 'ma_webinarjam_logs', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$log_type = isset( $_POST['log_type'] ) ? sanitize_key( $_POST['log_type'] ) : '';
		$result   = $this->clear_logs( $log_type );

		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * AJAX handler for exporting logs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_export_logs() {
		check_ajax_referer( 'ma_webinarjam_logs', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ma-plugin' ) );
		}

		$log_type = isset( $_GET['log_type'] ) ? sanitize_key( $_GET['log_type'] ) : '';
		$logs     = $this->get_logs( array(
			'type'  => $log_type,
			'limit' => 10000,
		) );

		// Set CSV headers.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=webinarjam-logs-' . gmdate( 'Y-m-d' ) . '.csv' );

		// Create file handle.
		$output = fopen( 'php://output', 'w' );

		// Add BOM for UTF-8.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Add header row.
		fputcsv( $output, array( 'Type', 'Message', 'Context', 'Date' ) );

		// Add data rows.
		foreach ( $logs as $log ) {
			fputcsv( $output, array(
				$log->log_type,
				$log->message,
				wp_json_encode( $log->context ),
				$log->created_at,
			) );
		}

		fclose( $output );
		exit;
	}
}

<?php
/**
 * WebinarJam Taxonomy Manager
 *
 * Manages taxonomy terms for WebinarJam integration.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Taxonomy Manager class.
 *
 * Handles creation and management of taxonomy terms required
 * for WebinarJam integration.
 *
 * @since 1.0.0
 */
class WebinarJam_Taxonomy {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_create_terms' ), 20 );
	}

	/**
	 * Create default taxonomy terms if they don't exist.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_create_terms() {
		// Check if terms have been created.
		if ( get_option( 'ma_webinarjam_terms_created' ) ) {
			return;
		}

		// Create course type terms.
		$this->create_course_type_terms();

		// Mark as created.
		update_option( 'ma_webinarjam_terms_created', time() );

		ma_log_webinarjam_debug( 'Created default WebinarJam taxonomy terms.' );
	}

	/**
	 * Create course type taxonomy terms.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function create_course_type_terms() {
		$taxonomy = 'course_type';

		// Check if taxonomy exists.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			ma_log_webinarjam_debug( "Taxonomy {$taxonomy} does not exist yet.", 'warning' );
			return;
		}

		$terms = array(
			'webinar' => array(
				'name'        => __( 'Webinar', 'ma-plugin' ),
				'slug'        => 'webinar',
				'description' => __( 'Live or replayed webinar courses integrated with WebinarJam.', 'ma-plugin' ),
			),
			'replay'  => array(
				'name'        => __( 'Replay', 'ma-plugin' ),
				'slug'        => 'replay',
				'description' => __( 'Webinar replays available on-demand.', 'ma-plugin' ),
			),
		);

		foreach ( $terms as $slug => $term_data ) {
			// Check if term already exists.
			$term = term_exists( $slug, $taxonomy );

			if ( ! $term ) {
				// Create the term.
				$result = wp_insert_term(
					$term_data['name'],
					$taxonomy,
					array(
						'slug'        => $term_data['slug'],
						'description' => $term_data['description'],
					)
				);

				if ( is_wp_error( $result ) ) {
					ma_log_webinarjam_debug(
						sprintf(
							'Failed to create term %s: %s',
							$slug,
							$result->get_error_message()
						),
						'error'
					);
				} else {
					ma_log_webinarjam_debug( "Created term: {$slug}" );
				}
			} else {
				ma_log_webinarjam_debug( "Term already exists: {$slug}" );
			}
		}
	}

	/**
	 * Get webinar course type term ID.
	 *
	 * @since 1.0.0
	 * @return int|false Term ID or false if not found.
	 */
	public static function get_webinar_term_id() {
		$term = get_term_by( 'slug', 'webinar', 'course_type' );
		return $term ? $term->term_id : false;
	}

	/**
	 * Get replay course type term ID.
	 *
	 * @since 1.0.0
	 * @return int|false Term ID or false if not found.
	 */
	public static function get_replay_term_id() {
		$term = get_term_by( 'slug', 'replay', 'course_type' );
		return $term ? $term->term_id : false;
	}

	/**
	 * Assign webinar type to course.
	 *
	 * @since 1.0.0
	 * @param int  $course_id Course ID.
	 * @param bool $append Whether to append or replace existing terms.
	 * @return array|WP_Error|false Array of term taxonomy IDs, WP_Error on failure, false if no taxonomy.
	 */
	public static function assign_webinar_type( $course_id, $append = false ) {
		if ( empty( $course_id ) ) {
			return false;
		}

		$term_id = self::get_webinar_term_id();

		if ( ! $term_id ) {
			return new \WP_Error( 'term_not_found', __( 'Webinar term not found.', 'ma-plugin' ) );
		}

		return wp_set_post_terms( $course_id, array( $term_id ), 'course_type', $append );
	}

	/**
	 * Assign replay type to course.
	 *
	 * @since 1.0.0
	 * @param int  $course_id Course ID.
	 * @param bool $append Whether to append or replace existing terms.
	 * @return array|WP_Error|false Array of term taxonomy IDs, WP_Error on failure, false if no taxonomy.
	 */
	public static function assign_replay_type( $course_id, $append = false ) {
		if ( empty( $course_id ) ) {
			return false;
		}

		$term_id = self::get_replay_term_id();

		if ( ! $term_id ) {
			return new \WP_Error( 'term_not_found', __( 'Replay term not found.', 'ma-plugin' ) );
		}

		return wp_set_post_terms( $course_id, array( $term_id ), 'course_type', $append );
	}

	/**
	 * Check if course has webinar type.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return bool True if course has webinar type.
	 */
	public static function has_webinar_type( $course_id ) {
		if ( empty( $course_id ) ) {
			return false;
		}

		return has_term( 'webinar', 'course_type', $course_id );
	}

	/**
	 * Check if course has replay type.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return bool True if course has replay type.
	 */
	public static function has_replay_type( $course_id ) {
		if ( empty( $course_id ) ) {
			return false;
		}

		return has_term( 'replay', 'course_type', $course_id );
	}

	/**
	 * Remove taxonomy terms on deactivation (optional).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function cleanup_terms() {
		// Only run if explicitly requested.
		if ( ! apply_filters( 'ma_webinarjam_cleanup_terms', false ) ) {
			return;
		}

		$terms = array( 'webinar', 'replay' );

		foreach ( $terms as $slug ) {
			$term = get_term_by( 'slug', $slug, 'course_type' );

			if ( $term ) {
				// Check if any courses are using this term.
				$courses = get_posts(
					array(
						'post_type'      => 'sfwd-courses',
						'posts_per_page' => 1,
						'tax_query'      => array(
							array(
								'taxonomy' => 'course_type',
								'field'    => 'term_id',
								'terms'    => $term->term_id,
							),
						),
						'fields'         => 'ids',
					)
				);

				if ( empty( $courses ) ) {
					// No courses using this term, safe to delete.
					wp_delete_term( $term->term_id, 'course_type' );
					ma_log_webinarjam_debug( "Deleted term: {$slug}" );
				} else {
					ma_log_webinarjam_debug( "Term {$slug} is in use, skipping deletion." );
				}
			}
		}

		delete_option( 'ma_webinarjam_terms_created' );
	}
}

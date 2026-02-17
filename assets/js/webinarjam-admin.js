/**
 * WebinarJam Admin Scripts
 *
 * Handles quick sync and admin interactions.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize quick sync handlers.
	 */
	function initQuickSync() {
		$(document).on('click', '.ma-quick-sync', function(e) {
			e.preventDefault();

			var $link = $(this);
			var courseId = $link.data('course-id');
			var nonce = $link.data('nonce');
			var originalText = $link.text();

			// Disable and show loading.
			$link.text(maWebinarJam.strings.syncing).css('opacity', '0.5');

			// Send AJAX request.
			$.ajax({
				url: maWebinarJam.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ma_force_sync_webinar',
					course_id: courseId,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success feedback.
						$link.text('✓ Synced').css('color', '#46b450');

						// Reload page after delay.
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						// Show error.
						$link.text(maWebinarJam.strings.syncFailed).css('color', '#dc3232');

						// Reset after delay.
						setTimeout(function() {
							$link.text(originalText).css('opacity', '1').css('color', '');
						}, 3000);
					}
				},
				error: function() {
					// Show error.
					$link.text(maWebinarJam.strings.syncFailed).css('color', '#dc3232');

					// Reset after delay.
					setTimeout(function() {
						$link.text(originalText).css('opacity', '1').css('color', '');
					}, 3000);
				}
			});
		});
	}

	/**
	 * Initialize on document ready.
	 */
	$(document).ready(function() {
		initQuickSync();
	});

})(jQuery);

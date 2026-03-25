/**
 * WebinarJam Frontend JavaScript
 *
 * Handles AJAX registration, countdown timers, and button interactions.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * WebinarJam Frontend Handler
	 */
	const maWebinarJamFrontend = {

		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.initCountdowns();
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function() {
			$(document).on('click', '.ma-webinar-button', this.handleButtonClick.bind(this));
		},

		/**
		 * Handle button click
		 */
		handleButtonClick: function(e) {
			const $button = $(e.currentTarget);
			const action = $button.data('action');
			const courseId = $button.data('course-id');

			// Allow direct links to proceed normally
			if (['watch', 'replay', 'purchase'].indexOf(action) !== -1) {
				return true;
			}

			// Prevent default for AJAX actions
			e.preventDefault();

			// Handle registration
			if (action === 'register') {
				this.registerForWebinar($button, courseId);
			} else if (action === 'registered') {
				// Already registered, do nothing
				return false;
			}
		},

		/**
		 * Register user for webinar via AJAX
		 */
		registerForWebinar: function($button, courseId) {
			// Show loading state
			this.setButtonLoading($button, true);

			$.ajax({
				url: maWebinarJam.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ma_register_webinar',
					nonce: maWebinarJam.nonce,
					course_id: courseId
				},
				success: (response) => {
					if (response.success) {
						this.showNotification('success', response.data.message);

						// Update button state
						$button
							.attr('data-action', 'registered')
							.find('.button-text')
							.text(maWebinarJam.strings.success);

						// Redirect if URL provided
						if (response.data.redirect_url) {
							setTimeout(() => {
								window.location.href = response.data.redirect_url;
							}, 1500);
						}
					} else {
						this.handleRegistrationError($button, response.data);
					}
				},
				error: () => {
					this.setButtonLoading($button, false);
					this.showNotification('error', maWebinarJam.strings.error);
				}
			});
		},

		/**
		 * Handle registration error
		 */
		handleRegistrationError: function($button, data) {
			this.setButtonLoading($button, false);

			// Show error message
			this.showNotification('error', data.message);

			// Handle specific actions
			if (data.action === 'purchase_required' || data.action === 'login_required') {
				// Redirect to purchase/login page
				if (data.redirect_url) {
					this.showNotification('info', maWebinarJam.strings.redirecting);
					setTimeout(() => {
						window.location.href = data.redirect_url;
					}, 2000);
				}
			}
		},

		/**
		 * Set button loading state
		 */
		setButtonLoading: function($button, loading) {
			if (loading) {
				$button.addClass('loading');
				$button.find('.button-text').hide();
				$button.find('.button-loader').show();
			} else {
				$button.removeClass('loading');
				$button.find('.button-text').show();
				$button.find('.button-loader').hide();
			}
		},

		/**
		 * Show notification
		 */
		showNotification: function(type, message) {
			// Remove existing notifications
			$('.webinar-notification').remove();

			// Create notification element
			const $notification = $('<div>', {
				class: 'webinar-notification ' + type,
				text: message
			});

			// Find best place to insert
			const $target = $('.ma-webinar-button').first().parent();

			if ($target.length) {
				$target.prepend($notification);
			} else {
				$('body').prepend($notification);
			}

			// Auto-remove after 5 seconds
			setTimeout(() => {
				$notification.fadeOut(() => {
					$notification.remove();
				});
			}, 5000);
		},

		/**
		 * Initialize countdown timers
		 */
		initCountdowns: function() {
			$('.ma-webinar-countdown').each((index, element) => {
				this.startCountdown($(element));
			});
		},

		/**
		 * Start countdown timer
		 */
		startCountdown: function($countdown) {
			const timestamp = parseInt($countdown.data('timestamp'), 10) * 1000;

			// Update immediately
			this.updateCountdown($countdown, timestamp);

			// Update every second
			setInterval(() => {
				this.updateCountdown($countdown, timestamp);
			}, 1000);
		},

		/**
		 * Update countdown display
		 */
		updateCountdown: function($countdown, targetTime) {
			const now = new Date().getTime();
			const distance = targetTime - now;

			// If countdown is finished
			if (distance < 0) {
				$countdown.find('.countdown-value').text('0');
				return;
			}

			// Calculate time units
			const days = Math.floor(distance / (1000 * 60 * 60 * 24));
			const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
			const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
			const seconds = Math.floor((distance % (1000 * 60)) / 1000);

			// Update display
			$countdown.find('.countdown-value.days').text(days);
			$countdown.find('.countdown-value.hours').text(hours);
			$countdown.find('.countdown-value.minutes').text(minutes);
			$countdown.find('.countdown-value.seconds').text(seconds);
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(() => {
		maWebinarJamFrontend.init();
	});

	/**
	 * Handle registration success from query string
	 */
	$(document).ready(() => {
		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get('webinar_registered') === '1') {
			maWebinarJamFrontend.showNotification(
				'success',
				'Successfully registered for webinar!'
			);

			// Clean URL
			if (window.history.replaceState) {
				const cleanUrl = window.location.pathname;
				window.history.replaceState({}, document.title, cleanUrl);
			}
		}
	});

})(jQuery);

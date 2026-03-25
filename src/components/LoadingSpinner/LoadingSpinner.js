/**
 * Medical Academic Enhancements LoadingSpinner
 * Accessible loading spinner for WordPress blocks and frontend.
 * @param root0
 * @param root0.label
 */
export default function LoadingSpinner({
	label = __('Loading…', 'ma-plugin'),
}) {
	return (
		<div
			className="ma_plugin-loading-spinner"
			role="status"
			aria-live="polite"
		>
			<span className="ma_plugin-spinner" aria-hidden="true">
				⏳
			</span>
			<span className="ma_plugin-loading-label">{label}</span>
		</div>
	);
}

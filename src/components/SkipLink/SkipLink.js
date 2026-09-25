/**
 * Medical Academic Enhancements SkipLink
 * Accessible skip to main content link for WordPress blocks and frontend.
 * @param root0
 * @param root0.target
 * @param root0.label
 */
export default function SkipLink({
	target = '#maincontent',
	label = __('Skip to main content', 'ma-plugin'),
}) {
	return (
		<a
			href={target}
			className="ma_plugin-skip-link sr-only"
			tabIndex={0}
		>
			{label}
		</a>
	);
}

/**
 * Medical Academic Enhancements Logo
 * Accessible logo component for WordPress blocks and frontend.
 * @param root0
 * @param root0.src
 * @param root0.alt
 */
export default function Logo({
	src,
	alt = __('Logo', 'ma-plugin'),
	...props
}) {
	return (
		<img
			src={src}
			alt={alt}
			className="ma_plugin-logo"
			role="img"
			{...props}
		/>
	);
}

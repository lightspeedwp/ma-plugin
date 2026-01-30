/**
 * Medical Academic Enhancements Divider
 * Accessible divider for WordPress blocks and frontend.
 * @param root0
 * @param root0.label
 */
export default function Divider({ label }) {
	return <hr className="ma_plugin-divider" aria-label={label} />;
}

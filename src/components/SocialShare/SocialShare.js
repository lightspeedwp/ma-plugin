/**
 * Medical Academic Enhancements SocialShare
 * Accessible social sharing buttons for frontend use.
 * @param root0
 * @param root0.url
 * @param root0.title
 */
export default function SocialShare({ url, title }) {
	const shareText = encodeURIComponent(title || document.title);
	const shareUrl = encodeURIComponent(url || window.location.href);
	return (
		<nav
			className="ma_plugin-social-share"
			aria-label={__('Share', 'ma-plugin')}
		>
			<ul>
				<li>
					<a
						href={`https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareText}`}
						target="_blank"
						rel="noopener noreferrer"
						aria-label={__('Share on Twitter', 'ma-plugin')}
					>
						Twitter
					</a>
				</li>
				<li>
					<a
						href={`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`}
						target="_blank"
						rel="noopener noreferrer"
						aria-label={__('Share on Facebook', 'ma-plugin')}
					>
						Facebook
					</a>
				</li>
				<li>
					<a
						href={`mailto:?subject=${shareText}&body=${shareUrl}`}
						aria-label={__('Share by Email', 'ma-plugin')}
					>
						Email
					</a>
				</li>
			</ul>
		</nav>
	);
}

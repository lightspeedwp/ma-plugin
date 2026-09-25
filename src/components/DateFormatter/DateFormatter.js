import { useMemo } from 'react';

/**
 * Medical Academic Enhancements DateFormatter (Relative Time)
 * Formats a date as relative time (e.g., "5 minutes ago").
 * @param root0
 * @param root0.date
 * @param root0.locale
 */
export default function DateFormatter({ date, locale = 'en' }) {
	const relTime = useMemo(() => {
		if (!date) {
			return '';
		}
		const now = new Date();
		const then = new Date(date);
		const diff = (now - then) / 1000;
		if (diff < 60) {
			return __('just now', 'ma-plugin');
		}
		if (diff < 3600) {
			return `${Math.floor(diff / 60)} ${__('minutes ago', 'ma-plugin')}`;
		}
		if (diff < 86400) {
			return `${Math.floor(diff / 3600)} ${__('hours ago', 'ma-plugin')}`;
		}
		if (diff < 604800) {
			return `${Math.floor(diff / 86400)} ${__('days ago', 'ma-plugin')}`;
		}
		return then.toLocaleDateString(locale);
	}, [date, locale]);
	return <time dateTime={date}>{relTime}</time>;
}

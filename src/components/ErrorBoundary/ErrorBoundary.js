import { Component } from 'react';

/**
 * Medical Academic Enhancements ErrorBoundary
 * Accessible error boundary for WordPress blocks and frontend.
 */
export class ErrorBoundary extends Component {
	constructor(props) {
		super(props);
		this.state = { hasError: false };
	}

	static getDerivedStateFromError() {
		return { hasError: true };
	}

	componentDidCatch(error, info) {
		if (this.props.onError) {
			this.props.onError(error, info);
		}
	}

	render() {
		if (this.state.hasError) {
			return (
				<div className="ma_plugin-error-boundary" role="alert">
					{__('Something went wrong.', 'ma-plugin')}
				</div>
			);
		}
		return this.props.children;
	}
}

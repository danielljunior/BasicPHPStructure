export {};

if (window.location.hostname === 'localhost') {
	const eventSource = new EventSource('http://localhost:35729');
	eventSource.onmessage = (event) => {
		if (event.data === 'reload') {
			window.location.reload();
		}
	};
	eventSource.onerror = () => {
		// Reconnect after a delay if connection is lost
		setTimeout(() => {
			window.location.reload();
		}, 1500);
	};
}

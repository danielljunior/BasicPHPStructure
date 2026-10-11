const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 35729;
let clients = [];
let reloadTimeout;

// Create SSE server
const server = http.createServer((req, res) => {
	res.writeHead(200, {
		'Content-Type': 'text/event-stream',
		'Cache-Control': 'no-cache',
		'Connection': 'keep-alive',
		'Access-Control-Allow-Origin': '*',
	});
	
	clients.push(res);
	
	req.on('close', () => {
		clients = clients.filter(c => c !== res);
	});
});

server.on('error', (err) => {
	if (err.code === 'EADDRINUSE') {
		console.warn(`\x1b[33m[Watcher] Warning: Port ${PORT} is already in use. Live reload is disabled for this session.\x1b[0m`);
	} else {
		console.error('[Watcher] Server error:', err);
	}
});

server.listen(PORT, () => {
	console.log(`[Watcher] Live Reload Server running on port ${PORT}`);
});

function reload() {
	console.log('[Watcher] Triggering browser reload...');
	clients.forEach(res => {
		res.write('data: reload\n\n');
	});
}

// Watch directory recursively for changes in .html, .css, .js, and .php files
function watchDirRecursive(dir) {
	try {
		fs.watch(dir, (eventType, filename) => {
			if (filename) {
				const ext = path.extname(filename);
				const isRelevant = ['.html', '.css', '.js', '.php'].includes(ext);
				
				if (isRelevant && !filename.includes('node_modules') && !filename.startsWith('.')) {
					console.log(`[Watcher] File change detected: ${filename}`);
					clearTimeout(reloadTimeout);
					reloadTimeout = setTimeout(reload, 150); // Debounce reloads
				}
			}
		});

		// Recursively register watchers on subdirectories
		fs.readdirSync(dir, { withFileTypes: true }).forEach(dirent => {
			if (dirent.isDirectory() && dirent.name !== 'node_modules' && dirent.name !== '.git') {
				watchDirRecursive(path.join(dir, dirent.name));
			}
		});
	} catch (err) {
		console.error(`[Watcher] Error watching ${dir}:`, err);
	}
}

// Start watching workspace directories
watchDirRecursive(path.join(__dirname, 'public'));
watchDirRecursive(path.join(__dirname, '../src'));

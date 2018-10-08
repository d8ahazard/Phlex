importScripts('./js/lib/src/support/12_cache-polyfill.js');
self.addEventListener('install', function (e) {
	e.waitUntil(
		caches.open('phlex').then(function (cache) {
			console.log("opening caches?");
			return cache.addAll([
				'./css/loader_main.css',
				'./css/lib/dist/support.css',
				'./css/main_max_576.css',
				'./css/main_max_768.css',
				'./css/main_min_992.css',
				'./css/main_min_1200.css',
				'./img/android-icon-36x36.png',
				'./img/android-icon-48x48.png',
				'./img/android-icon-72x72.png',
				'./img/android-icon-96x96.png',
				'./img/android-icon-144x144.png',
				'./img/android-icon-192x192.png',
				'./img/android-icon-384x384.png',
				'./img/android-icon-512x512.png',
				'./img/apple-icon.png',
				'./img/avatar.png',
				'./img/favicon.ico',
				'./img/phlex-med.png',
				'./js/lib/dist/support.js',
				'./js/lib/dist/ui.js'
			]);
		})
	);
});

self.addEventListener('fetch', function (event) {
	console.log(event.request.url);
	event.respondWith(
		caches.match(event.request).then(function (response) {
            if (event.request.cache === 'only-if-cached' && event.request.mode !== 'same-origin') return;
			return response || fetch(event.request);
		})
	);
});


function reload() {
	fetchData(true);
}
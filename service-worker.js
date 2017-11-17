importScripts('/js/cache-polyfill.js');
self.addEventListener('install', function (e) {
	e.waitUntil(
		caches.open('phlex').then(function (cache) {
			console.log("opening caches?");
			return cache.addAll([
				'/',
				'/css/font/Roboto-Black.ttf',
				'/css/font/Roboto-BlackItalic.ttf',
				'/css/font/Roboto-Bold.ttf',
				'/css/font/Roboto-BoldItalic.ttf',
				'/css/font/Roboto-Italic.ttf',
				'/css/font/Roboto-Light.ttf',
				'/css/font/Roboto-LightItalic.ttf',
				'/css/font/Roboto-Medium.ttf',
				'/css/font/Roboto-MediumItalic.ttf',
				'/css/font/Roboto-Regular.ttf',
				'/css/font/Roboto-Thin.ttf',
				'/css/font/Roboto-ThinItalic.ttf',
				'/css/font/MaterialIcons.woff2',
				'/css/bootstrap.min.css',
				'/css/bootstrap-dialog.css',
				'/css/bootstrap-grid.min.css',
				'/css/bootstrap-ie8.css',
				'/css/bootstrap-material-design.min.css',
				'/css/dark.css',
				'/css/font-awesome.min.css',
				'/css/fonts.css',
				'/css/main.css',
				'/css/main_max_400.css',
				'/css/main_max_600.css',
				'/css/main_min_600.css',
				'/css/main_min_2000.css',
				'/css/material.css',
				'/css/ripples.min.css',
				'/css/snackbar.min.css',
				'/img/android-icon-36x36.png',
				'/img/android-icon-48x48.png',
				'/img/android-icon-72x72.png',
				'/img/android-icon-96x96.png',
				'/img/android-icon-144x144.png',
				'/img/android-icon-192x192.png',
				'/img/android-icon-384x384.png',
				'/img/android-icon-512x512.png',
				'/img/apple-icon.png',
				'/img/avatar.png',
				'/img/favicon.ico',
				'/img/phlex.png',
				'/js/arrive.min.js',
				'/js/bootstrap.min.js',
				'/js/bootstrap-dialog.js',
				'/js/clipboard.min.js',
				'/js/ie10-viewport-bug-workaround.js',
				'/js/jquery.simpleWeather.min.js',
				'/js/jquery-3.2.1.min.js',
				'/js/login.js',
				'/js/main.js',
				'/js/material.min.js',
				'/js/nouislider.min.js',
				'/js/ripples.min.js',
				'/js/run_prettify.js',
				'/js/snackbar.min.js',
				'/js/swiped.min.js',
				'/js/tether.min.js'
			]);
		})
	);
});

self.addEventListener('fetch', function (event) {
	console.log(event.request.url);
	event.respondWith(
		caches.match(event.request).then(function (response) {
			return response || fetch(event.request);
		})
	);
});
<?php
require_once dirname(__FILE__) . '/util.php';
/*

	@name 			Server Logs Viewer
	@description 	Emulates the tail() function. View the latest lines of your LAMP server logs in your browser.
	@author 		Alexandre Plennevaux (pixeline.be)
	@team			Oleg Basov (olegeech@sytkovo.su)
	@date 			16.12.2015

*/

/* Absolute local path to your server 'log' directory */
//define('LOG_PATH','/var/log');
$logPath = file_build_path(dirname(__FILE__), 'logs');
define('LOG_PATH', $logPath);
define('DISPLAY_REVERSE', true); // true = displays log entries starting with the most recent
define('DIRECTORY_SEPARATOR', '/');

$log = (!isset($_GET['p'])) ? $logPath : urldecode($_GET['p']);
$lines = (!isset($_GET['lines'])) ? 50 : $_GET['lines'];


$files = get_log_files(LOG_PATH);

ksort($files);
foreach ($files as $dir_name => $file_array) {
	ksort($file_array);
}
$filename = $log;
$title = substr($log, (strrpos($log, '/') + 1));
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Logs viewer</title>
	<meta name="description" content="PHP Script that presents your Server Logs in an easy to use layout.">
	<meta name="author" content="pixeline.be">
	<link rel="stylesheet" href="https://unpkg.com/purecss@0.6.2/build/pure-min.css"
		  integrity="sha384-UQiGfs9ICog+LwheBSRCt1o5cbyKIHbwjWscjemyBMT9YCUMZffs6UqUTd0hObXD" crossorigin="anonymous">
	<style type="text/css" media="screen">

		body {
			color: #777;
		}

		pre {
			font-size: 14px;
			font-family: monospace;
			color: black;
			line-height: 1;
			white-space: pre-wrap;
		}

		/*
		Add transition to containers so they can push in and out.
		*/
		#layout,
		#menu,
		.menu-link {
			-webkit-transition: all 0.2s ease-out;
			-moz-transition: all 0.2s ease-out;
			-ms-transition: all 0.2s ease-out;
			-o-transition: all 0.2s ease-out;
			transition: all 0.2s ease-out;
		}

		/*
		This is the parent `<div>` that contains the menu and the content area.
		*/
		#layout {
			position: relative;
			padding-left: 0;
		}

		#layout.active {
			position: relative;
			left: 200px;
		}

		#layout.active #menu {
			left: 200px;
			width: 200px;
		}

		#layout.active .menu-link {
			left: 200px;
		}

		/*
		The content `<div>` is where all your content goes.
		*/
		.content {
			margin: 0 auto;
			padding: 0 2em;
			max-width: 800px;
			margin-bottom: 50px;
			line-height: 1.6em;
		}

		.header {
			margin: 0;
			color: #333;
			text-align: center;
			padding: 2.5em 2em;
			border-bottom: 1px solid #eee;
		}

		.header h1 {
			margin: 0.2em 0;
			font-size: 3em;
			font-weight: 300;
		}

		.header h2 {
			font-weight: 300;
			color: #ccc;
			padding: 0;
			margin-top: 0;
		}

		.content-subhead {
			margin: 50px 0 20px 0;
			font-weight: 300;
			color: #888;
		}

		/*
		The `#menu` `<div>` is the parent `<div>` that contains the `.pure-menu` that
		appears on the left side of the page.
		*/

		#menu {
			margin-left: -200px; /* "#menu" width */
			width: 250px;
			position: fixed;
			top: 0;
			left: 0;
			bottom: 0;
			z-index: 1000; /* so the menu or its navicon stays above all content */
			background: #191818;
			overflow-y: auto;
			-webkit-overflow-scrolling: touch;
		}

		/*
		All anchors inside the menu should be styled like this.
		*/
		#menu a {
			color: #999;
			border: none;

			font-size: .8rem;
			line-height: 1rem;
		}

		/*
		Remove all background/borders, since we are applying them to #menu.
		*/
		#menu .pure-menu,
		#menu .pure-menu ul {
			border: none;
			background: transparent;
		}

		/*
		Add that light border to separate items into groups.
		*/
		#menu .pure-menu ul,
		#menu .pure-menu .menu-item-divided {
			border-top: 1px solid #333;
		}

		/*
		Change color of the anchor links on hover/focus.
		*/
		#menu .pure-menu li a:hover,
		#menu .pure-menu li a:focus {
			background: #333;
			color: #FFF;
		}

		/*
		This styles the selected menu item `<li>`.
		*/
		#menu .pure-menu-selected,
		#menu .pure-menu-heading {
			background: #1f8dd6;
		}

		/*
		This styles a link within a selected menu item `<li>`.
		*/
		#menu .pure-menu-selected a {
			color: #ffffff;
		}

		/*
		This styles the menu heading.
		*/
		#menu .pure-menu-heading {
			font-size: 110%;
			font-weight: 300;
			letter-spacing: 0.1em;
			color: #fff;
			margin-top: 0;
			padding: 0.5em 0.8em;
			background: transparent;
		}

		.credits {
			border-top: 1px solid #DDD;
			color: #CCC;
			font-size: .8rem;
			text-align: center;
			margin-top: 50px
		}

		.credits a {
			color: #CCC;
		}

		/* -- Dynamic Button For Responsive Menu -------------------------------------*/

		.menu-link {
			position: fixed;
			display: block; /* show this only on small screens */
			top: 0;
			left: 0; /* "#menu width" */
			background: #000;
			background: rgba(0, 0, 0, 0.7);
			font-size: 10px; /* change this value to increase/decrease button size */
			z-index: 10;
			width: 2em;
			height: auto;
			padding: 2.1em 1.6em;
		}

		.menu-link:hover,
		.menu-link:focus {
			background: #000;
		}

		.menu-link span {
			position: relative;
			display: block;
		}

		.menu-link span,
		.menu-link span:before,
		.menu-link span:after {
			background-color: #fff;
			width: 100%;
			height: 0.2em;
		}

		.menu-link span:before,
		.menu-link span:after {
			position: absolute;
			margin-top: -0.6em;
			content: " ";
		}

		.menu-link span:after {
			margin-top: 0.6em;
		}

		/* -- Responsive Styles (Media Queries) ------------------------------------- */

		/*
		Hides the menu at `48em`, but modify this based on your app's needs.
		*/
		@media (min-width: 48em) {

			.header,
			.content {
				padding-left: 2em;
				padding-right: 2em;
			}

			#layout {
				padding-left: 200px; /* left col width "#menu" */
				left: 0;
			}

			#menu {
				left: 200px;
			}

			.menu-link {
				position: fixed;
				left: 200px;
				display: none;
			}

			#layout.active .menu-link {
				left: 200px;
			}
		}

		.truncate {
			width: 100px;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		ol {
			list-style: decimal-leading-zero;
			list-style-position: outside;
		}

		ol li {
			border-bottom: 1px solid #DDD;
			color: #CCC;
			font-weight: 100;
			font-size: 12px;
		}

		ol li:last-child {
			border-bottom: 0px solid #DDD;
		}

		ol li pre {
			height: auto;
			overflow: visible;
		}
	</style>
</head>

<body>
<div id="layout">
	<!-- Menu toggle -->
	<a href="#menu" id="menuLink" class="pure-menu-heading">
		<!-- Hamburger icon -->
		<span></span>
	</a>

	<div id="menu">
		<div class="pure-menu pure-menu-open">
			<a href="<?php echo $_SERVER['PHP_SELF'] ?>" class="pure-menu-heading">Server Logs</a>
			<ul>
				<?php show_list_of_files($files, $lines); ?>

			</ul>
		</div>
	</div>

	<div id="main">
		<div class="header">
			<h1><?php echo $title; ?></h1>
			<?= (!empty($filename)) ? '<h2>The last ' . $lines . ' lines of <span class="truncate">' . basename($filename) . '</span></h2>' : ''; ?>

			<form action="" method="get" class="pure-form pure-form-aligned">
				<input type="hidden" name="p" value="<?php echo $log ?>">
				<label>How many lines to display?
					<select name="lines" onchange="this.form.submit()">
						<option value="10" <?php echo ($lines == '10') ? 'selected' : '' ?>>10</option>
						<option value="50" <?php echo ($lines == '50') ? 'selected' : '' ?>>50</option>
						<option value="100" <?php echo ($lines == '100') ? 'selected' : '' ?>>100</option>
						<option value="500" <?php echo ($lines == '500') ? 'selected' : '' ?>>500</option>
						<option value="1000" <?php echo ($lines == '1000') ? 'selected' : '' ?>>1000</option>
					</select>
				</label>
			</form>
		</div>

		<div class="content">

			<ol reversed>
				<?php
				$output = tail($filename, $lines);

				if ($output) {
					$output = explode("\n", $output);
					if (DISPLAY_REVERSE) {
						// Latest first
						$output = array_reverse($output);
					}
					$output = implode('</pre><li><pre>', $output);
					echo $output;
				} else {
					?>
					<ul>

						<?php show_list_of_files($files, $lines); ?>


					</ul>
					<?php
				}


				?>
			</ol>

			<footer>
				<p class="credits"><a href="//pixeline.be">Script by pixeline</a>, thanks to <a href="//purecss.io/">purecss.io</a>
				</p>
			</footer>
		</div>
	</div>
</div>
<script>
	(function (window, document) {

		var layout = document.getElementById('layout'),
			menu = document.getElementById('menu'),
			menuLink = document.getElementById('menuLink');

		function toggleClass(element, className) {
			var classes = element.className.split(/\s+/),
				length = classes.length,
				i = 0;

			for (; i < length; i++) {
				if (classes[i] === className) {
					classes.splice(i, 1);
					break;
				}
			}
			// The className is not found
			if (length === classes.length) {
				classes.push(className);
			}

			element.className = classes.join(' ');
		}

		menuLink.onclick = function (e) {
			var active = 'active';

			e.preventDefault();
			toggleClass(layout, active);
			toggleClass(menu, active);
			toggleClass(menuLink, active);
		};

	}(this, this.document));

</script>
</body>
</html>
<?php

function get_log_files($dir, &$results = []) {
	$files = scandir($dir);
	if ($files) {
		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);

			if (!is_dir($path)) {
				$files_list[] = $path;
			} elseif ($value != "." && $value != "..") {
				$dirs_list[] = $path;
			}
		}

		foreach ($files_list as $path) {
			preg_match("/^.*\/(\S+)$/", $path, $matches);
			$name = $matches[1];
			$results[$dir][$name] = ['name' => $name, 'path' => $path];
		}
		if (count($dirs_list) > 0) {
			foreach ($dirs_list as $path) {
				get_log_files($path, $results);
			}
		}
		return $results;
	}
	return false;
}

function tail($filename, $lines = 50, $buffer = 4096) {
	// Open the file
	if (!is_file($filename)) {
		return false;
	}
	$f = fopen($filename, "rb");
	if (!$f) {
		return false;
	}

	// Jump to last character
	fseek($f, -1, SEEK_END);

	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if (fread($f, 1) != "\n") $lines -= 1;

	// Start reading
	$output = '';
	$chunk = '';

	// While we would like more
	while (ftell($f) > 0 && $lines >= 0) {
		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);

		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);

		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)) . $output;

		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");
	}

	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ($lines++ < 0) {
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}

	// Close file and return
	fclose($f);
	return $output;
}

function show_list_of_files($files, $lines = 50) {
	if (empty($files)) {
		return false;
	}

	// Generate a menu
	foreach ($files as $dir => $files_array) {
		//echo '<li>'.dirname($dir).'</li>';
		echo '<ul>';
		foreach ($files_array as $k => $f) {
			if (!is_file($f['path'])) {
				// File does not exist, remove it from the array, so it does not appear in the menu.
				unset($files_array[$k]);
				continue;
			}
			$active = ($f['path'] == $log) ? 'class="pure-menu-selected"' : '';
			echo '<li ' . $active . '><a href="?p=' . urlencode($f['path']) . '&lines=' . $lines . '">' . $f['name'] . '</a></li>';
		}
		echo '</ul>';
	}
}
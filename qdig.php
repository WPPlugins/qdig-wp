<?php
/*
Plugin Name: Qdig-WP
Plugin URI: http://randomfrequency.net/wordpress/qdig/
Description: Allows you to embed <a href="http://qdig.sourceforge.net/">Qdig (Quick Digital Image Gallery)</a> galleries in posts or pages.
Version: 0.5
Author: David B. Nagle
Author URI: http://randomfrequency.net/
*/ 

/*
== License ==

Copyright 2005 David Nagle
This program is distributed under the terms of the
GNU General Public License, Version 2

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, Version 2 as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License,
Version 2 along with this program; if not, visit GNU's Home Page
http://www.gnu.org/
*/

function qdig_embed($content) {
	global $post;
	// Only embed the gallery if this is a page or single post
	if(is_page() || is_single()) {
	
		if(preg_match("/\[qdig-disable\]/i", $content, $matches)) {
			$replace = "/\\". $matches[0] ."/";
			$content = preg_replace($replace, '', $content, 1);
		} elseif(preg_match("/\[qdig.*\]/i", $content, $matches)) {
			$replace = $matches[0];
		
			if(preg_match("/\spath=(\S*?)[\]\s]/i", $replace, $matches)) {
				$_GET['Qwd'] = $matches[1];
			}

			if(preg_match("/\ssize=(S|M|L|XL|FS)[\]\s]/i", $replace, $matches)) {
				$_GET['Qis'] = $matches[1];
			}

			if(preg_match("/\slinks=(thumbs|name|num|none)[\]\s]/i", $replace, $matches)) {
				$_GET['Qiv'] = $matches[1];
			}
			
			// This globalizes the variables qdig expects to be at global scope
			eval(rf_scrape_globals(ABSPATH.'wp-content/plugins/qdig/index.php', -1));

			ob_start();
			include(ABSPATH . 'wp-content/plugins/qdig/index.php');
			$gallery_content = ob_get_contents();
			ob_end_clean();

			$content = str_replace($replace, $gallery_content, $content);
		}
	}
	return $content;
}

function qdig_embed_alt($content) {
	if(!(is_page() || is_single())) {
		if(preg_match("/\[qdig-disable\]/i", $content, $matches)) {
			$replace = "/\\". $matches[0] ."/";
			$content = preg_replace($replace, '', $content, 1);
		} elseif(preg_match("/\[qdig.*\]/i", $content, $matches)) {
			$replace = $matches[0];
			if(preg_match("/\salt='([^']*)'[\]\s]/i", $replace, $matches)) {
				$link_text = $matches[1];
			} elseif(preg_match("/\salt=\"([^\"]*)\"[\]\s]/i", $replace, $matches)) {
				$link_text = $matches[1];
			} elseif(preg_match("/\salt=(\S*?)[\]\s]/i", $replace, $matches)) {
				$link_text = $matches[1];
			} else {
				$link_text = "Visit Gallery";
			}
			$gallery_link = '<a href="'. get_permalink() . '">'. $link_text .'</a>';
			$content = str_replace($replace, $gallery_link, $content);
		}
	}
	return $content;
}

if (! function_exists('rf_scrape_globals')) {
	function rf_scrape_globals($filename, $cols = 79) {
		// PHP Scrape PHP Globals
		// Version 0.1
		// http://randomfrequency.net/misc-code/scrape-php-globals/


		// Get filecontents
		$lines = file($filename);

		// Convert file to a single string
		$file = implode('', $lines);
		unset($lines);

		// Remove /* ... */
		$file = preg_replace('/\057\052.*?\052\057/s','',$file);
		// Remove // ...
		$file = preg_replace('/\/\/.*?$/m','',$file);

		// Find all global statements
		preg_match_all("/global .*?;/s", $file, $globals, PREG_PATTERN_ORDER);

		$vars = array();
		
		foreach($globals[0] as $g) {
			// Get rid of the non-variable parts
			$g = preg_replace('/^global /s','',$g);
			$g = preg_replace('/;$/s','',$g);
			// Convert all series of space characters to single spaces
			$g = preg_replace('/\s+/',' ',$g);
			// Extract all variables
			$temp = explode(', ', $g);
			$vars = array_merge($temp, $vars);
		}

		// Figure out the minimum length required for output line
		$minlen = strlen("global");

		// Populate a hash with all the variable names
		foreach ($vars as $v) {
			$hash[$v] = 1;
			// Add 1 for the comma/semicolon
			if(strlen($v)+1 > $minlen) $minlen = strlen($v)+1;
		}

		// Coerce cols up to minlen if necessary
		if($cols < $minlen && $cols >= 0) $cols = $minlen;

		// Sort array hash
		ksort($hash);

		// Create a new global command with all the variables
		$disp = implode(', ', array_keys($hash));
		$disp = "global $disp;";

		if($cols >= 0) {
			// Generate the command, restrained by column width
			$comma = $cols-1;
			while($disp) {
				preg_match("/^.{1,$cols}$|^.{1,$comma},/", $disp, $matches);
				$line = $matches[0];
				$disp = substr_replace($disp, '', 0, strlen($line));
				$disp = preg_replace('/^ /','',$disp);
				$output .= "$line\n";
			}
		} else {
			$output = "$disp\n";
		}

		return $output;
	}
}

/* The wptexturize function runs on the_content and the_excerpt at level 10. If
   it runs before qdig_embed_alt, it screws up the quotes. If it runs after
   qdig_embed, it screws up the code generated by qdig.
*/

add_filter('the_content', 'qdig_embed_alt', 9);
add_filter('the_excerpt', 'qdig_embed_alt', 9);
add_filter('the_content', 'qdig_embed', 10);
?>

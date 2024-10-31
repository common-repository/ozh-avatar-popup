<?php
/*
Plugin Name: Ozh' Avatar Popup
Plugin URI: http://planetozh.com/blog/my-projects/wordpress-plugin-avatar-gravatar-popup/
Description: Adds an avatar popup to mailto links (and to any other word if desired)
Version: 1.1
Author: Ozh
Author URI: http://planetOzh.com
*/

/*************************************
*	  OPTIONAL EDIT BELOW		  *
*			   ~~				  *
*************************************/


$wp_ozh_avatar['size'] = 80;
	// Size of the gravatar displayed (in pixels)

$wp_ozh_avatar['default'] = 'http://yoursite.com/images/gravatar_default.gif';// 'http://yourblog.com/blog/images/default_gravatar.gif';
	// The url of the default gravatar, to be used when email adress is not known on www.gravatar.com
	// Can be of any size, won't be affected by size defined in previous setting
	// A few examples of default gravatar :
	//   http://blog.cssbasics.com/images/no_gravatar.jpg
	//   http://planetozh.com/blog/wp-content/themes/planetozh/images/gravatar_default.gif
	//   http://www.dce.ac.nz/images/ed_support/ed_support_advisers/avatar.jpg
	//   http://www.music.mcgill.ca/~benjamin/pictures/homo.jpg . No, just kidding :-)
   	// Please DON'T USE these images, they are just examples if you need to make your own
	// You can use mine if you mirror it on *your* site (ie do not link the image on my site from yours)

$wp_ozh_avatar['css'] = <<<AVATARPOPCSS
<style type="text/css" media="screen">
a.avatarpop, span.avatarpop {
	position:relative  !important;
	text-decoration: none !important;
	border-bottom: 1px dotted silver !important;
}
a.avatarpop img, span.avatarpop img {
	display: none !important;
}
a.avatarpop:hover img, span.avatarpop:hover img {
	display: block !important;
	position: absolute !important;
	padding: 3px !important;
	margin: 10px !important;
	top: 1.5em !important;
	z-index: 100 !important;
	color: #ddd !important;
	background: white !important;
	border-top:1px solid #ddd !important;
	border-right:1px solid #ddd !important;
	border-bottom:1px solid #555 !important;
	border-left:1px solid #555 !important;
}
</style>
AVATARPOPCSS;
	// Style of popups Gravatars
	// Put style definition for class .avatarpop, enclosed in <<<AVATARPOPCSS and AVATARPOPCSS;
	// (Warning : the closing AVATARPOPCSS; must not be indented)

$wp_ozh_avatar['printcss'] = 1;
	// If you want, and I would recommend it, add the style definitions for .avatarpop directly
	// in your main CSS, and set this setting to 0. This will spare a few bits added on each page in
	// the <head> section


 /*************************************
*		DO NOT EDIT BELOW		  *
*			   ~~				  *
*************************************/

/**************************************************************************************************************************/

/*
* input  : <a href="mailto:ozh@web" otherhtmltags>Ozh</a>
* output : <a class="avatarpop" href="mailto:ozh@web" otherhtmltags>Ozh<img src="gravatar" /></a>
* (gravatar of email address specified)

* input  : <a href="mailto:ozh@web" noavatar otherhtmltags>Ozh</a>
* output : <a href="mailto:ozh@web" otherhtmltags>Ozh</a>
* (nothing added)

* input  : [avatar:ozh@web]Ozh[/avatar]
* output : <span class="avatarpop">Ozh<img src="gravatar" /></span>
* (gravatar of email address specified)

* input  : [avatar:comment]Ozh[/avatar]
* output : <span class="avatarpop">Ozh<img src="gravatar" /></span>
* (gravatar of last email address used in the comments)

* input  : [avatar:something]Ozh[/avatar]
* output : <span class="avatarpop">Ozh<img src="something" /></span>
* (URI of an image)

* input  : [mailto:Ozh]Me[/mailto]
* output : <a class="avatarpop" href="mailto:ozh@web">Me<img src="gravatar" /></a>
* (gravatar of last email address used in the comments)

*/

function wp_ozh_avatar ($input) {
	if (is_feed()) {
		$input = preg_replace('|\[/?avatar:?[^\]]*\]|', '', $input);
		$input = preg_replace('|\[/?mailto:?[^\]]*\]|', '', $input);
	} else {
		$input = preg_replace_callback ('/<a href="mailto:([^"]*)"([^>]*)>(.+?)<\/a>/', 'wp_ozh_avatar_email', $input);
		$input = preg_replace_callback ('/\[avatar:([^\]]*)\](.*?)\[\/avatar\]/', 'wp_ozh_avatar_span', $input);
		$input = preg_replace_callback ('/\[mailto:([^\]]*)\](.*?)\[\/mailto\]/', 'wp_ozh_avatar_mailto', $input);
	}
	return $input;
}


function wp_ozh_avatar_email ($input) {
	global $wp_ozh_avatar;

	$email = strtolower($input[1]);
	$html = trim($input[2]);
	$name = $input[3];
	if (eregi('noavatar', $html)) {
		$html = trim(str_replace('noavatar', '', $html));
		return "<a href=\"mailto:". antispambot($email) ."\" $html>$name</a>";
	} else {
		if (eregi('class=\"([^"]+)\"', $html, $regs)) {
			$html = ereg_replace($regs[1], $regs[1].' avatarpop', $html);
			$class= '';
		} else {
			$class='class="avatarpop"';
		}
		$grav_url = "http://www.gravatar.com/avatar.php?gravatar_id=".md5($email)."&amp;default=".urlencode($wp_ozh_avatar['default'])."&amp;size=".$wp_ozh_avatar['size'];
		$img = "<img src=\"$grav_url\" alt=\"$name\" />";
		return "<a $class href=\"mailto:". antispambot($email) ."\" $html>$name$img</a>";
	}
}

function wp_ozh_avatar_span ($input) {
	global $wp_ozh_avatar, $wpdb;

	$avatar = $input[1];
	$name = $input[2];
	if ($avatar == 'comment') {
		$avatar = $wpdb->get_var("SELECT comment_author_email FROM $wpdb->comments where comment_author = '$name' ORDER BY comment_ID DESC LIMIT 1");
	}
	if (eregi('.+\@.+',$avatar)) {
		$src = "http://www.gravatar.com/avatar.php?gravatar_id=".md5(strtolower($avatar))."&amp;default=".urlencode($wp_ozh_avatar['default'])."&amp;size=".$wp_ozh_avatar['size'];
	} else {
		$src = $avatar;
	}
	$img = "<img src=\"$src\" alt=\"\" />";
	return "<span class=\"avatarpop\">$name$img</span>";
}

function wp_ozh_avatar_mailto ($input) {
	global $wp_ozh_avatar, $wpdb;

	$nick = $input[1];
	$name = $input[2];
	$email = strtolower($wpdb->get_var("SELECT comment_author_email FROM $wpdb->comments where comment_author = '$mailto' ORDER BY comment_ID DESC LIMIT 1"));
	$src = "http://www.gravatar.com/avatar.php?gravatar_id=".md5($email)."&amp;default=".urlencode($wp_ozh_avatar['default'])."&amp;size=".$wp_ozh_avatar['size'];
	$img = "<img src=\"$src\" alt=\"\" />";
	return "<a href=\"mailto:". antispambot($email) ."\" class=\"avatarpop\">$name$img</a>";
}

function wp_ozh_avatar_style() {
	global $wp_ozh_avatar;
	print $wp_ozh_avatar['css'];
}


add_filter('the_content', 'wp_ozh_avatar');
add_filter('the_excerpt_rss', 'wp_ozh_avatar');
if ($wp_ozh_avatar['printcss']) add_filter('wp_head', 'wp_ozh_avatar_style');


?>

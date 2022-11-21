<?php
/**
 * taxonomy-newsletter.php
 *
 * This is the default email template for Newsletters
 * (newsletter taxonomy showing story type posts).
 *
 * To modify this template:
 *
 * - Create these subfolders in your theme's root folder:
 *
 *   groups-newsletters
 *   groups-newsletters/email
 *
 * - Make a copy of this file there and adjust the copy as desired.
 *
 * @author itthinx
 * @package groups-newsletters
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

 /**
 * The newsletter rendered by this template.
 *
 * @var $newsletter Groups_Newsletters_Newsletter
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo get_bloginfo( 'charset' ); ?>" />
<title><?php echo wp_strip_all_tags( $newsletter->term->name ); ?></title>
<style type="text/css">
body {
	line-height: 1.62em;
	color: #333;
	margin: 1em;
}
.alignleft {
	display: inline;
	float: left;
	margin-right: 1.6em;
}
.alignright {
	display: inline;
	float: right;
	margin-left: 1.6em;
}
.aligncenter {
	clear: both;
	display: block;
	margin: 0 auto;
}

.clear:before,
.clear:after,
[class*="content"]:before,
[class*="content"]:after,
[class*="site"]:before,
[class*="site"]:after {
	content: '';
	display: table;
}

.clear:after,
[class*="content"]:after,
[class*="site"]:after {
	clear: both;
}

.wp-caption {
	border: 1px solid #ccc;
	margin-bottom: 1.6em;
	max-width: 100%;
}
.wp-caption img[class*="wp-image-"] {
	display: block;
	margin: 1.6% auto 0;
	max-width: 98%;
}
.wp-caption-text {
	text-align: center;
}
.wp-caption .wp-caption-text {
	margin: 0.62em 0;
}
.gallery {
	margin-bottom: 1.6em;
}
.gallery-caption {
	background-color: rgba(0, 0, 0, 0.6);
	color: #fff;
	font-size: 12px;
	font-style: italic;
	margin: 0;
	max-height: 50%;
	position: absolute;
	bottom: 10px;
	left: 4px;
	text-align: left;
	width: 90%;
}
.gallery a img {
	border: none;
	height: auto;
	max-width: 90%;
}
.gallery dd {
	margin: 0;
}
.gallery-item {
	float:left;
	margin: 0 4px 4px 0;
	overflow: hidden;
	position:relative;
}
.gallery-item img {
	padding:4px;
}
</style>
</head>
<body>
<?php
echo '<div class="newsletter email">';

// newsletter title
echo sprintf( '<h1 class="newsletter-title %s">%s</h1>', $newsletter->term->slug, wp_strip_all_tags( $newsletter->term->name ) );

// newsletter description
if ( !empty( $newsletter->term->description ) ) {
	echo '<div class="newsletter-description">';
	echo wp_filter_kses( $newsletter->term->description );
	echo '</div>';
}

echo '<div class="stories">';

// Newsletter content: stories
// Note that some 'the_content' filters rely on the global $post - we set it
// to the current post to be displayed and reestablish it after looping over
// our stories.
global $post;
if ( isset( $post ) ) {
	$_post = $post;
}
$post_ids = $newsletter->get_post_ids();
foreach( $post_ids as $post_id ) {
	if ( $post = get_post( $post_id ) ) {
		if ( !class_exists( 'Groups_Post_Access' ) || Groups_Post_Access::user_can_read_post( $post_id ) ) {
			$story = new Groups_Newsletters_Story( $post );
			echo '<div class="story">';
			echo '<h2 class="story-title">';
			echo $story->get_title();
			echo '</h2>';
			echo '<div class="story-content">';
			echo $story->get_content();
			echo '</div>'; // .story-content
			echo '</div>'; // .story
		}
	}
}
if ( isset( $_post ) ) {
	$post = $_post;
}

echo '</div>'; // .stories

echo '</div>'; // .newsletter.email

echo '<div class="unsubscribe">';
_e( 'You are receiving this because you are subscribed to our newsletters. You can cancel your subscription immediately by visiting this link: [unsubscribe_link]', 'groups-newsletters' );
echo '</div>';
?>
</body>
</html>

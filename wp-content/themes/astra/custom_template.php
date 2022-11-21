<?php /* Template Name: Custom Template */ ?>
<?php
get_header();
?>
<div>
<?php
$content = apply_filters( 'the_content', get_the_content() );
echo $content;
?>
</div>
<?php
get_footer();
?>
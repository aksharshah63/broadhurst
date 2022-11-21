<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<?php astra_content_bottom(); ?>

</div> <!-- ast-container -->
</div>  
</div><!-- #content -->
<?php 
	astra_content_after();
		
	astra_footer_before();
		
	astra_footer();
		
	astra_footer_after(); 
?>
</div><!-- #page -->
<?php 
	astra_body_bottom();    
	wp_footer(); 
?>
<script>
$ = jQuery;
$(document).ready(function() {
    //load_posts(1);
    $('.load-more-bottom-btn').css("display", "none");
    $(document).on('click', '#more_posts', function() {
        var page = $(this).attr('p');
        load_posts(page);
    });
});

function load_posts(pageNumber) {
    var ppp = 3;
    var ajax_url = '<?= get_site_url()?>/wp-admin/admin-ajax.php';
    var str = '&pageNumber=' + pageNumber + '&ppp=' + ppp + '&action=more_post_ajax';
    $.ajax({
        type: "POST",
        dataType: "html",
        url: ajax_url,
        data: str,
        success: function(data) {
            $('#more_posts').remove();
            $("#ajax-posts").append(data);
			$('.load-more-bottom-btn').css("display", "block");
			console.log($('#more_posts').text());
			if($('#more_posts').text()=="")
			   {
			   		$('.load-more-bottom-btn').css("margin-top", "200px");
			   }
			
			$(window).resize(function() {
  if ($(this).width() < 767) {
	$('.load-more-bottom-btn').css("margin-top", "150px");
  } else {
    $('.load-more-bottom-btn').css("margin-top", "200px");
  }
});
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $loader.html(jqXHR + " :: " + textStatus + " :: " + errorThrown);
        }

    });
    return false;
}
</script>
</body>

</html>
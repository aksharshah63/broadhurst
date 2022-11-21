function cp_display_redirect_url( value ){
	if ( value == 'yes' ){
		jQuery ( '#redirect-private-pages-url' ).show();
	}else{
		jQuery ( '#redirect-private-pages-url' ).hide();
	}
}

jQuery(function() {
	cp_display_redirect_url( jQuery( '#redirect-private-pages' ).val() );
});
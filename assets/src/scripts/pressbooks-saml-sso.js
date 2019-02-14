jQuery( function ( $ ) {
	let idp_metadata_url = $( '#idp_metadata_url' );
	idp_metadata_url.on( 'input', function () {
		if ( ! this.value.trim() ) {
			// is empty or whitespace
			$( '#manual-configuration :input' ).attr( 'disabled', false );
		} else {
			$( '#manual-configuration :input' ).attr( 'disabled', 'disabled' );
		}
	} );
	let checkbox = $( '#forced_redirection' );
	$( '#button_text' ).prop( 'disabled', checkbox.is( ':checked' ) );
	checkbox.on( 'change', function () {
		$( '#button_text' ).prop( 'disabled', $( this ).is( ':checked' ) )
	} );
} );

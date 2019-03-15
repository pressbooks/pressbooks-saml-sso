jQuery( function ( $ ) {
	let idpMetadataUrl = $( '#idp_metadata_url' );
	idpMetadataUrl.on( 'input', function () {
		let matches = document.querySelectorAll( '#manual-configuration input, textarea' );
		matches.forEach( element => {
			if ( ! this.value.trim() ) {
				// is empty or whitespace
				element.removeAttribute( 'disabled' );
			} else {
				element.setAttribute( 'disabled', 'disabled' );
			}
		} );
	} );
	// A jQuery object is an array-like wrapper around one or more DOM elements. To get a reference to the actual DOM elements (instead of the jQuery object) use array notation
	let buttonText = $( '#button_text' );
	let checkbox = $( '#forced_redirection' );
	checkbox[0].checked ? buttonText[0].setAttribute( 'disabled', 'disabled' ) : buttonText[0].removeAttribute( 'disabled' );
	checkbox.on( 'change', function () {
		this.checked ? buttonText[0].setAttribute( 'disabled', 'disabled' ) : buttonText[0].removeAttribute( 'disabled' );
	} );
} );

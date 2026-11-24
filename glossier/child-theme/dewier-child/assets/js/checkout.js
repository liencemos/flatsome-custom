/**
 * Checkout steps (Information / Shipping / Payment), recap boxes, summary toggle and discount code.
 */
( function ( $ ) {
	'use strict';

	var root = document.querySelector( '.dw-co' );
	if ( ! root ) {
		return;
	}

	var $root = $( root );
	var steps = root.dataset.steps.split( ' ' );
	var current = steps[ 0 ];
	var text = window.dewierCheckout || {};

	function section( step ) {
		return $root.find( '.dw-co__step[data-step="' + step + '"]' );
	}

	// WooCommerce's own field validation, run on the visible fields of one step.
	function validate( step ) {
		var $step = section( step );
		$step.find( '.form-row' ).filter( ':visible' ).find( '.input-text, select, input:checkbox' ).trigger( 'validate' );
		var $bad = $step.find( '.form-row.woocommerce-invalid' ).filter( ':visible' );
		if ( $bad.length ) {
			$bad.first().find( 'input, select, textarea' ).first().trigger( 'focus' );
			return false;
		}
		return true;
	}

	function value( id ) {
		var $field = $( '#' + id );
		if ( ! $field.length || ! $field.val() ) {
			return '';
		}
		if ( $field.is( 'select' ) ) {
			return $field.find( 'option:selected' ).text().trim();
		}
		return String( $field.val() ).trim();
	}

	function address() {
		var type = $( '#ship-to-different-address-checkbox' ).is( ':checked' ) ? 'shipping' : 'billing';
		return [ 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ].map( function ( key ) {
			return value( type + '_' + key );
		} ).filter( Boolean ).join( ', ' );
	}

	function method() {
		var $input = $root.find( '.dw-co-shipping-methods input.shipping_method:checked, .dw-co-shipping-methods input.shipping_method[type="hidden"]' ).first();
		return $input.length ? $root.find( 'label[for="' + $input.attr( 'id' ) + '"]' ).text().trim() : '';
	}

	function row( label, content, target ) {
		var $row = $( '<div class="dw-co__recap-row"></div>' );
		$( '<span class="dw-co__recap-label"></span>' ).text( label ).appendTo( $row );
		$( '<span class="dw-co__recap-value"></span>' ).text( content ).appendTo( $row );
		$( '<button type="button" class="dw-co__recap-change"></button>' ).attr( 'data-go', target ).text( text.change || 'Change' ).appendTo( $row );
		return $row;
	}

	function recap() {
		$root.find( '.dw-co__recap' ).each( function () {
			var $box = $( this ).empty();
			$box.append( row( text.contact || 'Contact', value( 'billing_email' ), 'information' ) );
			if ( steps.indexOf( 'shipping' ) > -1 ) {
				$box.append( row( text.shipTo || 'Ship to', address(), 'information' ) );
				if ( 'payment' === $box.data( 'recap' ) && method() ) {
					$box.append( row( text.method || 'Method', method(), 'shipping' ) );
				}
			}
		} );
	}

	function go( step, skipCheck ) {
		var from = steps.indexOf( current );
		var to = steps.indexOf( step );
		if ( to < 0 ) {
			return;
		}
		if ( ! skipCheck ) {
			for ( var i = from; i < to; i++ ) {
				if ( ! validate( steps[ i ] ) ) {
					go( steps[ i ], true );
					return;
				}
			}
		}
		current = step;
		root.dataset.current = step;
		steps.forEach( function ( key, index ) {
			section( key ).prop( 'hidden', key !== step );
			$root.find( '.dw-co__crumb[data-go="' + key + '"]' )
				.toggleClass( 'is-done', index < to )
				.attr( 'aria-current', key === step ? 'step' : null )
				.prop( 'disabled', index > to );
		} );
		recap();
		if ( from !== to ) {
			$( document.body ).trigger( 'update_checkout' );
			var top = root.getBoundingClientRect().top + window.pageYOffset - 16;
			if ( window.pageYOffset > top ) {
				window.scrollTo( { top: top, behavior: 'smooth' } );
			}
			section( step ).find( '.dw-co__heading' ).first().attr( 'tabindex', '-1' ).trigger( 'focus' );
		}
	}

	root.classList.add( 'is-stepped' );
	go( current, true );

	$root.on( 'click', '[data-go]', function ( event ) {
		event.preventDefault();
		go( this.dataset.go );
	} );

	// Enter in a field moves on a step; before Payment it would otherwise place the order.
	$root.on( 'keydown', 'form.checkout input:not(#dw-co-coupon):not([type="submit"])', function ( event ) {
		if ( 'Enter' !== event.key || event.originalEvent.isComposing || 'payment' === current ) {
			return;
		}
		event.preventDefault();
		go( steps[ steps.indexOf( current ) + 1 ] );
	} );

	$( document.body ).on( 'updated_checkout', recap );

	// Server-side errors: return to the step that holds the first invalid field.
	$( document.body ).on( 'checkout_error', function () {
		var id = $( '.woocommerce-NoticeGroup-checkout [data-id]' ).first().data( 'id' );
		var $step = id ? $( '#' + id ).closest( '.dw-co__step' ) : $();
		if ( $step.length && $step.data( 'step' ) !== current ) {
			go( $step.data( 'step' ), true );
		}
	} );

	// Order summary collapses behind a toggle on small screens.
	$root.on( 'click', '.dw-co-toggle', function () {
		var open = 'true' !== this.getAttribute( 'aria-expanded' );
		this.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		root.classList.toggle( 'is-summary-open', open );
	} );

	// Discount code: forwards to WooCommerce's coupon form so its AJAX flow and notices still apply.
	var $code = $( '#dw-co-coupon' );
	var errored = false;

	function note( message, isError ) {
		var $note = $root.find( '.dw-co-coupon__note' );
		if ( ! $note.length ) {
			$note = $( '<p class="dw-co-coupon__note" role="status"></p>' ).insertAfter( $root.find( '.dw-co-coupon' ) );
		}
		$note.text( message ).toggleClass( 'is-error', !! isError ).prop( 'hidden', ! message );
	}

	function applyCode() {
		var $form = $( 'form.checkout_coupon' );
		var code = String( $code.val() || '' ).trim();
		if ( ! code || ! $form.length ) {
			$code.trigger( 'focus' );
			return;
		}
		errored = false;
		note( '' );
		$form.find( 'input[name="coupon_code"]' ).val( code );
		$form.trigger( 'submit' );
	}

	$root.on( 'click', '.dw-co-coupon__apply', applyCode );
	$code.on( 'keydown', function ( event ) {
		if ( 'Enter' === event.key ) {
			event.preventDefault();
			applyCode();
		}
	} );

	$( document.body ).on( 'errored_coupon_in_checkout', function ( event, code, response ) {
		errored = true;
		note( $( $.parseHTML( response ) ).text().trim(), true );
	} );
	$( document.body ).on( 'applied_coupon_in_checkout', function () {
		if ( ! errored ) {
			$code.val( '' );
			note( text.applied || 'Discount applied.' );
		}
	} );
}( jQuery ) );

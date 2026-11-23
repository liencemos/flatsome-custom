/* Dewier storefront interactions. */
( function () {
	'use strict';

	var doc = document;
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function all( selector, root ) {
		return Array.prototype.slice.call( ( root || doc ).querySelectorAll( selector ) );
	}

	/* Promo bar: cross-fading announcements. */
	function initPromo() {
		var bar = doc.querySelector( '.dw-promo' );
		var slides = bar ? all( '.dw-promo__slide', bar ) : [];
		if ( slides.length < 2 ) {
			return;
		}
		var index = 0;
		var timer = null;
		var paused = reduceMotion;
		var toggle = bar.querySelector( '[data-promo="toggle"]' );

		function show( next ) {
			index = ( next + slides.length ) % slides.length;
			slides.forEach( function ( slide, i ) {
				var active = i === index;
				slide.classList.toggle( 'is-active', active );
				slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
				slide.tabIndex = active ? 0 : -1;
			} );
		}

		function schedule() {
			clearInterval( timer );
			if ( ! paused ) {
				timer = setInterval( function () {
					show( index + 1 );
				}, 5000 );
			}
		}

		function setPaused( value ) {
			paused = value;
			toggle.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
			schedule();
		}

		bar.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-promo]' );
			if ( ! button ) {
				return;
			}
			if ( 'toggle' === button.dataset.promo ) {
				setPaused( ! paused );
				return;
			}
			show( index + ( 'next' === button.dataset.promo ? 1 : -1 ) );
			schedule();
		} );

		setPaused( paused );
	}

	/* Header: sticky shadow, mobile menu, search panel. */
	function initHeader() {
		var header = doc.querySelector( '.dw-header' );
		if ( ! header ) {
			return;
		}
		var nav = header.querySelector( '.dw-nav' );
		var toggle = header.querySelector( '.dw-header__toggle' );
		var overlay = doc.querySelector( '.dw-overlay' );
		var searchButton = header.querySelector( '.dw-utils__search' );
		var search = doc.getElementById( 'dw-search' );

		function onScroll() {
			header.classList.toggle( 'is-stuck', window.scrollY > 40 );
		}
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();

		function setMenu( open ) {
			nav.classList.toggle( 'is-open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			overlay.hidden = ! open;
			doc.documentElement.style.overflow = open ? 'hidden' : '';
			if ( open ) {
				nav.querySelector( '.dw-nav__close' ).focus();
			}
		}

		toggle.addEventListener( 'click', function () {
			setMenu( ! nav.classList.contains( 'is-open' ) );
		} );
		nav.querySelector( '.dw-nav__close' ).addEventListener( 'click', function () {
			setMenu( false );
			toggle.focus();
		} );
		overlay.addEventListener( 'click', function () {
			setMenu( false );
		} );

		function setSearch( open ) {
			search.hidden = ! open;
			searchButton.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				search.querySelector( 'input[type="search"]' ).focus();
			}
		}

		searchButton.addEventListener( 'click', function () {
			setSearch( search.hidden );
		} );

		doc.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' !== event.key ) {
				return;
			}
			if ( nav.classList.contains( 'is-open' ) ) {
				setMenu( false );
				toggle.focus();
			}
			if ( ! search.hidden ) {
				setSearch( false );
				searchButton.focus();
			}
		} );
	}

	/* Carousels: arrows, progress bar. */
	function initCarousel( carousel ) {
		var track = carousel.querySelector( '.dw-carousel__track' );
		var bar = carousel.querySelector( '.dw-carousel__progress span' );
		var prev = carousel.querySelector( '.dw-carousel__arrow--prev' );
		var next = carousel.querySelector( '.dw-carousel__arrow--next' );

		function update() {
			var max = track.scrollWidth - track.clientWidth;
			var ratio = track.clientWidth / track.scrollWidth;
			var progress = max > 0 ? track.scrollLeft / max : 0;
			carousel.classList.toggle( 'is-static', max <= 1 );
			bar.style.width = ( ratio * 100 ) + '%';
			bar.style.left = ( progress * ( 1 - ratio ) * 100 ) + '%';
			prev.hidden = track.scrollLeft <= 1;
			next.hidden = track.scrollLeft >= max - 1;
		}

		carousel.addEventListener( 'click', function ( event ) {
			var arrow = event.target.closest( '.dw-carousel__arrow' );
			if ( ! arrow ) {
				return;
			}
			var item = track.querySelector( '.dw-carousel__list > li' );
			var step = item ? item.getBoundingClientRect().width + 16 : track.clientWidth;
			var count = Math.max( 1, Math.floor( track.clientWidth / step ) );
			track.scrollBy( { left: Number( arrow.dataset.dir ) * step * count, behavior: reduceMotion ? 'auto' : 'smooth' } );
		} );

		track.addEventListener( 'scroll', function () {
			window.requestAnimationFrame( update );
		}, { passive: true } );
		window.addEventListener( 'resize', update );
		update();
	}

	/* Product cards: a shade or size choice sets what "Add to bag" adds. */
	function initCardOptions() {
		doc.addEventListener( 'click', function ( event ) {
			var option = event.target.closest( '.dw-card .dw-option[data-variation]' );
			if ( ! option ) {
				return;
			}
			var card = option.closest( '.dw-card' );
			all( '.dw-option[data-variation]', card ).forEach( function ( item ) {
				var selected = item === option;
				item.classList.toggle( 'is-selected', selected );
				item.setAttribute( 'aria-checked', selected ? 'true' : 'false' );
			} );
			var button = card.querySelector( '.add_to_cart_button' );
			if ( button ) {
				button.dataset.product_id = option.dataset.variation;
				button.setAttribute( 'data-product_id', option.dataset.variation );
			}
			var price = card.querySelector( '.price' );
			if ( price && option.dataset.price ) {
				price.innerHTML = option.dataset.price;
			}
		} );

		all( '.dw-card .dw-option.is-selected[data-price]' ).forEach( function ( option ) {
			var price = option.closest( '.dw-card' ).querySelector( '.price' );
			if ( price ) {
				price.innerHTML = option.dataset.price;
			}
		} );
	}

	/* Bag: open the drawer once an AJAX add completes. */
	function initBag() {
		var $ = window.jQuery;
		if ( ! $ ) {
			return;
		}
		function openDrawer() {
			var trigger = doc.querySelector( '.shoptimizer-cart .cart-contents' );
			if ( trigger && ! doc.body.classList.contains( 'drawer-open' ) ) {
				trigger.click();
			}
		}
		if ( doc.querySelector( '.single-product .woocommerce-message' ) ) {
			window.setTimeout( openDrawer, 300 );
		}
		$( doc.body ).on( 'added_to_cart', openDrawer );
	}

	/* Product page: swatches, button price, gallery, description. */
	function initProduct() {
		var page = doc.querySelector( '.dw-pdp' );
		if ( ! page ) {
			return;
		}
		var form = page.querySelector( 'form.cart' );
		var button = page.querySelector( '.single_add_to_cart_button' );
		var priceEl = null;

		if ( button ) {
			button.innerHTML = '<span>' + button.textContent.trim() + ' <span class="dw-atc-price"></span></span>';
			priceEl = button.querySelector( '.dw-atc-price' );
			priceEl.textContent = page.dataset.price || '';
		}

		all( '.dw-options--pdp', page ).forEach( function ( group ) {
			var select = form.querySelector( 'select[name="' + group.dataset.attribute + '"]' );
			var note = group.parentNode.querySelector( '.dw-option-note' );
			if ( ! select ) {
				return;
			}

			function sync() {
				var current = null;
				all( '.dw-option', group ).forEach( function ( option ) {
					var selected = option.dataset.value === select.value;
					option.classList.toggle( 'is-selected', selected );
					option.setAttribute( 'aria-checked', selected ? 'true' : 'false' );
					if ( selected ) {
						current = option;
					}
				} );
				if ( note ) {
					note.textContent = current ? current.dataset.name + ( note.dataset.note && select.selectedIndex === 1 ? ' ' + note.dataset.note : '' ) + '.' : '';
				}
			}

			group.addEventListener( 'click', function ( event ) {
				var option = event.target.closest( '.dw-option' );
				if ( ! option ) {
					return;
				}
				select.value = option.dataset.value;
				if ( window.jQuery ) {
					window.jQuery( select ).trigger( 'change' );
				} else {
					select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
				sync();
			} );

			select.addEventListener( 'change', sync );
			sync();
		} );

		if ( window.jQuery && form && priceEl ) {
			window.jQuery( form ).on( 'found_variation', function ( event, variation ) {
				var holder = doc.createElement( 'div' );
				holder.innerHTML = variation.price_html || '';
				var amount = holder.querySelector( 'ins .amount, .amount:last-child' ) || holder.querySelector( '.amount' );
				if ( amount ) {
					priceEl.textContent = amount.textContent;
				} else if ( variation.display_price ) {
					priceEl.textContent = '$' + variation.display_price;
				}
			} );
		}

		var slides = all( '.dw-pdp__slide', page );
		var thumbs = all( '.dw-pdp__thumb', page );
		thumbs.forEach( function ( thumb ) {
			thumb.addEventListener( 'click', function () {
				slides.forEach( function ( slide ) {
					slide.classList.toggle( 'is-active', slide.dataset.index === thumb.dataset.index );
				} );
				thumbs.forEach( function ( item ) {
					var active = item === thumb;
					item.classList.toggle( 'is-active', active );
					item.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				} );
			} );
		} );

		var desc = page.querySelector( '.dw-pdp__desc' );
		if ( desc ) {
			var body = desc.querySelector( '.dw-pdp__desc-body' );
			var more = desc.querySelector( '.dw-link' );
			if ( body.scrollHeight <= body.clientHeight + 2 ) {
				more.hidden = true;
			}
			more.addEventListener( 'click', function () {
				var collapsed = 'true' === desc.dataset.collapsed;
				desc.dataset.collapsed = collapsed ? 'false' : 'true';
				more.setAttribute( 'aria-expanded', collapsed ? 'true' : 'false' );
				more.textContent = collapsed ? more.dataset.less : more.dataset.more;
			} );
		}
	}

	function init() {
		initPromo();
		initHeader();
		all( '.dw-carousel' ).forEach( initCarousel );
		initCardOptions();
		initBag();
		initProduct();
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

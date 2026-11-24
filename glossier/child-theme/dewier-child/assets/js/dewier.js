/* Meredithzone storefront interactions. */
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

		// The checkout header has only the logo.
		if ( ! nav || ! toggle ) {
			return;
		}

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
			var select = ( group.closest( 'form' ) || form ).querySelector( 'select[name="' + group.dataset.attribute + '"]' );
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

			// jQuery-triggered changes (WooCommerce, CommerceKit's sticky copy) skip native listeners.
			if ( window.jQuery ) {
				window.jQuery( select ).on( 'change', sync );
			} else {
				select.addEventListener( 'change', sync );
			}
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

	/* Help page: FAQ accordion, topic menu and search suggestions. */
	function initHelp() {
		function setOpen( item, open ) {
			item.classList.toggle( 'is-open', open );
			item.querySelector( '.dw-faq__q' ).setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		function jump( target ) {
			target.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' } );
		}

		function reveal( item ) {
			setOpen( item, true );
			if ( window.history.replaceState ) {
				window.history.replaceState( null, '', '#' + item.id );
			}
			jump( item );
			item.querySelector( '.dw-faq__q' ).focus( { preventScroll: true } );
		}

		function openFromHash() {
			var item = doc.getElementById( decodeURIComponent( window.location.hash.slice( 1 ) ) );
			if ( item && item.classList.contains( 'dw-faq__item' ) ) {
				setOpen( item, true );
			}
		}

		// Wraps each occurrence of a search word in the text in <mark>.
		function highlight( target, text, words ) {
			var lower = text.toLowerCase();
			var from = 0;
			while ( lower.length === text.length && from < text.length ) {
				var start = -1;
				var length = 0;
				words.forEach( function ( word ) {
					var at = lower.indexOf( word, from );
					if ( at > -1 && ( -1 === start || at < start || ( at === start && word.length > length ) ) ) {
						start = at;
						length = word.length;
					}
				} );
				if ( -1 === start ) {
					break;
				}
				var mark = doc.createElement( 'mark' );
				mark.textContent = text.slice( start, start + length );
				target.appendChild( doc.createTextNode( text.slice( from, start ) ) );
				target.appendChild( mark );
				from = start + length;
			}
			target.appendChild( doc.createTextNode( text.slice( from ) ) );
		}

		// Marks the topic being read in the topic list.
		function spy() {
			spying = false;
			all( '.dw-faq__nav' ).forEach( function ( nav ) {
				var current = null;
				var links = all( 'a', nav );
				links.forEach( function ( link ) {
					var topic = doc.getElementById( link.getAttribute( 'href' ).slice( 1 ) );
					if ( topic && topic.getBoundingClientRect().top <= window.innerHeight * 0.35 ) {
						current = link;
					}
				} );
				links.forEach( function ( link ) {
					if ( link === current ) {
						link.setAttribute( 'aria-current', 'true' );
					} else {
						link.removeAttribute( 'aria-current' );
					}
				} );
			} );
		}

		function requestSpy() {
			if ( ! spying ) {
				spying = true;
				window.requestAnimationFrame( spy );
			}
		}

		var spying = false;

		function parts( target ) {
			var form = target && target.closest ? target.closest( '.dw-help__search' ) : null;
			return form ? {
				form: form,
				input: form.querySelector( '.dw-help__input' ),
				list: form.querySelector( '.dw-help__results' ),
				clear: form.querySelector( '.dw-help__clear' ),
				status: form.querySelector( '.dw-help__status' )
			} : null;
		}

		function closeList( s ) {
			s.list.hidden = true;
			s.input.setAttribute( 'aria-expanded', 'false' );
			s.input.removeAttribute( 'aria-activedescendant' );
		}

		function search( s ) {
			var words = s.input.value.toLowerCase().split( /\s+/ ).filter( Boolean );
			var inQuestion = [];
			var inAnswer = [];
			s.clear.hidden = ! s.input.value;
			s.list.textContent = '';
			s.input.removeAttribute( 'aria-activedescendant' );
			if ( ! words.length ) {
				s.status.textContent = '';
				closeList( s );
				return;
			}
			all( '.dw-faq__item' ).forEach( function ( item ) {
				// Skip questions in a widget that is hidden at this screen size.
				if ( ! item.getClientRects().length ) {
					return;
				}
				var question = item.querySelector( '.dw-faq__q' ).textContent.toLowerCase();
				var text = question + ' ' + item.querySelector( '.dw-faq__a' ).textContent.toLowerCase();
				function within( haystack ) {
					return words.every( function ( word ) {
						return haystack.indexOf( word ) > -1;
					} );
				}
				if ( within( question ) ) {
					inQuestion.push( item );
				} else if ( within( text ) ) {
					inAnswer.push( item );
				}
			} );
			var hits = inQuestion.concat( inAnswer );
			hits.forEach( function ( item, i ) {
				var option = doc.createElement( 'li' );
				option.id = s.list.id + '-' + i;
				option.setAttribute( 'role', 'option' );
				option.setAttribute( 'aria-disabled', 'false' );
				option.dataset.target = item.id;
				var hit = doc.createElement( 'span' );
				hit.className = 'dw-help__hit';
				highlight( hit, item.querySelector( '.dw-faq__q' ).textContent.trim(), words );
				option.appendChild( hit );
				var title = item.closest( '.dw-faq__topic' ).querySelector( '.dw-faq__topic-title' );
				if ( title ) {
					var topic = doc.createElement( 'span' );
					topic.className = 'dw-help__hit-topic';
					topic.textContent = title.textContent;
					option.appendChild( topic );
				}
				s.list.appendChild( option );
			} );
			if ( ! hits.length ) {
				var none = doc.createElement( 'li' );
				none.setAttribute( 'role', 'option' );
				none.setAttribute( 'aria-disabled', 'true' );
				none.textContent = s.form.dataset.empty;
				s.list.appendChild( none );
			}
			s.status.textContent = hits.length ? s.form.dataset.count.replace( '%d', hits.length ) : s.form.dataset.empty;
			s.list.hidden = false;
			s.input.setAttribute( 'aria-expanded', 'true' );
		}

		function options( s ) {
			return all( '[data-target]', s.list );
		}

		function activate( s, step ) {
			var items = options( s );
			var index = items.indexOf( s.list.querySelector( '.is-active' ) ) + step;
			if ( ! items.length ) {
				return;
			}
			index = index < 0 ? items.length - 1 : index % items.length;
			items.forEach( function ( option, i ) {
				option.classList.toggle( 'is-active', i === index );
				option.setAttribute( 'aria-selected', i === index ? 'true' : 'false' );
			} );
			s.input.setAttribute( 'aria-activedescendant', items[ index ].id );
			items[ index ].scrollIntoView( { block: 'nearest' } );
		}

		function choose( s, option ) {
			var item = option ? doc.getElementById( option.dataset.target ) : null;
			if ( item ) {
				closeList( s );
				reveal( item );
			}
		}

		doc.addEventListener( 'click', function ( event ) {
			var question = event.target.closest( '.dw-faq__q' );
			var option = event.target.closest( '.dw-help__results [data-target]' );
			var s = parts( event.target );
			if ( question ) {
				var item = question.closest( '.dw-faq__item' );
				setOpen( item, ! item.classList.contains( 'is-open' ) );
			} else if ( option ) {
				choose( s, option );
			} else if ( s && event.target.closest( '.dw-help__clear' ) ) {
				s.input.value = '';
				search( s );
				s.input.focus();
			}
		} );

		doc.addEventListener( 'change', function ( event ) {
			var topic = event.target.matches( '.dw-faq__select' ) ? doc.getElementById( event.target.value ) : null;
			if ( topic ) {
				jump( topic );
				event.target.value = '';
			}
		} );

		doc.addEventListener( 'input', function ( event ) {
			if ( event.target.matches( '.dw-help__input' ) ) {
				search( parts( event.target ) );
			}
		} );

		doc.addEventListener( 'focusin', function ( event ) {
			if ( event.target.matches( '.dw-help__input' ) && event.target.value ) {
				search( parts( event.target ) );
			}
		} );

		doc.addEventListener( 'focusout', function ( event ) {
			var s = parts( event.target );
			if ( s && ! s.form.contains( event.relatedTarget ) ) {
				closeList( s );
			}
		} );

		doc.addEventListener( 'mousedown', function ( event ) {
			if ( event.target.closest( '.dw-help__results' ) ) {
				event.preventDefault();
			}
		} );

		doc.addEventListener( 'keydown', function ( event ) {
			var s = event.target.matches( '.dw-help__input' ) ? parts( event.target ) : null;
			if ( ! s ) {
				return;
			}
			if ( 'ArrowDown' === event.key || 'ArrowUp' === event.key ) {
				event.preventDefault();
				if ( s.list.hidden ) {
					search( s );
				}
				activate( s, 'ArrowDown' === event.key ? 1 : -1 );
			} else if ( 'Escape' === event.key && ! s.list.hidden ) {
				closeList( s );
			}
		} );

		doc.addEventListener( 'submit', function ( event ) {
			var s = event.target.matches( '.dw-help__search' ) ? parts( event.target ) : null;
			if ( s ) {
				event.preventDefault();
				if ( s.list.hidden ) {
					search( s );
				}
				choose( s, s.list.querySelector( '.is-active' ) || options( s )[0] );
			}
		} );

		// Shortcuts to topics that are not on the page are left out.
		all( '.dw-help__chip' ).forEach( function ( chip ) {
			var topic = doc.getElementById( chip.getAttribute( 'href' ).slice( 1 ) );
			chip.parentNode.hidden = ! topic || ! topic.getClientRects().length;
		} );

		if ( doc.querySelector( '.dw-faq__nav' ) ) {
			window.addEventListener( 'scroll', requestSpy, { passive: true } );
			window.addEventListener( 'resize', requestSpy );
			spy();
		}

		window.addEventListener( 'hashchange', openFromHash );
		openFromHash();
	}

	function init() {
		initPromo();
		initHeader();
		all( '.dw-carousel' ).forEach( initCarousel );
		initCardOptions();
		initBag();
		initProduct();
		initHelp();
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

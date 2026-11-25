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

		syncCardPrices( doc );
	}

	function syncCardPrices( root ) {
		all( '.dw-card .dw-option.is-selected[data-price]', root ).forEach( function ( option ) {
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

	function store( key, value ) {
		try {
			if ( undefined === value ) {
				return JSON.parse( window.localStorage.getItem( key ) );
			}
			if ( null === value ) {
				window.localStorage.removeItem( key );
			} else {
				window.localStorage.setItem( key, JSON.stringify( value ) );
			}
		} catch ( e ) {}
		return null;
	}

	function getJSON( url, options ) {
		return window.fetch( url, options ).then( function ( response ) {
			return response.json().catch( function () {
				return {};
			} ).then( function ( data ) {
				if ( ! response.ok ) {
					throw data;
				}
				return data;
			} );
		} );
	}

	/* Keeps Tab inside an open dialog. */
	function trapFocus( event, dialog ) {
		var items = all( 'a[href], button:not([disabled]), input:not([type="hidden"]):not([tabindex="-1"]), [tabindex="0"]', dialog ).filter( function ( item ) {
			return item.getClientRects().length;
		} );
		if ( 'Tab' !== event.key || ! items.length ) {
			return;
		}
		var first = items[0];
		var last = items[ items.length - 1 ];
		if ( event.shiftKey && ( doc.activeElement === first || doc.activeElement === dialog ) ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && doc.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	/* Email popup: shown once after a delay or when the pointer leaves the page. */
	function initNewsletter() {
		var root = doc.getElementById( 'dw-nl' );
		if ( ! root || ! window.fetch ) {
			return;
		}
		var KEY = 'dw_nl';
		var saved = store( KEY ) || {};
		var dialog = root.querySelector( '.dw-nl__dialog' );
		var form = root.querySelector( '.dw-nl__form' );
		var done = root.querySelector( '.dw-nl__done' );
		var msg = root.querySelector( '.dw-nl__msg' );
		var input = form.querySelector( 'input[type="email"]' );
		var lastFocus = null;
		var asked = 'joined' === saved.s || ( 'closed' === saved.s && Date.now() - saved.t < 30 * 864e5 );

		function open() {
			if ( ! root.hidden ) {
				return;
			}
			if ( doc.body.classList.contains( 'drawer-open' ) || doc.querySelector( '.dw-nav.is-open, .dw-lightbox:not([hidden])' ) ) {
				window.setTimeout( open, 5000 );
				return;
			}
			asked = true;
			lastFocus = doc.activeElement;
			root.hidden = false;
			doc.documentElement.classList.add( 'dw-nl-lock' );
			window.requestAnimationFrame( function () {
				root.classList.add( 'is-open' );
			} );
			( form.hidden ? dialog : input ).focus( { preventScroll: true } );
		}

		function close() {
			if ( root.hidden ) {
				return;
			}
			root.classList.remove( 'is-open' );
			root.hidden = true;
			doc.documentElement.classList.remove( 'dw-nl-lock' );
			if ( 'joined' !== ( store( KEY ) || {} ).s ) {
				store( KEY, { s: 'closed', t: Date.now() } );
			}
			if ( lastFocus && lastFocus.focus ) {
				lastFocus.focus( { preventScroll: true } );
			}
		}

		if ( ! asked ) {
			window.setTimeout( function () {
				if ( ! asked ) {
					open();
				}
			}, 8000 );
			doc.addEventListener( 'mouseout', function ( event ) {
				if ( ! asked && ! event.relatedTarget && event.clientY <= 0 ) {
					open();
				}
			} );
		}

		doc.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[href="#newsletter"]' );
			if ( link ) {
				event.preventDefault();
				open();
			}
		} );

		root.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-nl-close]' ) ) {
				close();
			}
			var copy = event.target.closest( '[data-nl-copy]' );
			if ( copy && window.navigator.clipboard ) {
				window.navigator.clipboard.writeText( done.querySelector( 'code' ).textContent ).then( function () {
					copy.textContent = copy.dataset.copied;
				} );
			}
		} );

		root.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				close();
			}
			trapFocus( event, dialog );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var button = form.querySelector( 'button[type="submit"]' );
			msg.textContent = '';
			if ( ! input.checkValidity() || ! input.value.trim() ) {
				msg.textContent = input.validationMessage || input.placeholder;
				input.focus();
				return;
			}
			button.disabled = true;
			button.classList.add( 'loading' );
			getJSON( root.dataset.endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( { email: input.value.trim(), company: form.elements.company.value } ),
			} ).then( function ( data ) {
				store( KEY, { s: 'joined', t: Date.now() } );
				done.querySelector( 'code' ).textContent = data.code;
				done.querySelector( '.dw-nl__status' ).textContent = data.message;
				form.hidden = true;
				done.hidden = false;
				root.querySelector( '.dw-nl__skip' ).hidden = true;
				done.querySelector( '[data-nl-copy]' ).focus();
			} ).catch( function ( error ) {
				msg.textContent = ( error && error.message ) || input.placeholder;
			} ).then( function () {
				button.disabled = false;
				button.classList.remove( 'loading' );
			} );
		} );
	}

	/* Review photos: multipart form, previews and a lightbox. */
	function initReviews() {
		var field = doc.querySelector( '.dw-review-upload' );
		if ( field ) {
			var form = field.closest( 'form' );
			var file = field.querySelector( 'input[type="file"]' );
			var preview = field.querySelector( '.dw-review-upload__preview' );
			var error = field.querySelector( '.dw-review-upload__error' );
			form.enctype = 'multipart/form-data';
			field.hidden = false;
			file.addEventListener( 'change', function () {
				var files = Array.prototype.slice.call( file.files );
				var bad = files.some( function ( item ) {
					return ! /^image\/(jpeg|png|webp)$/.test( item.type ) || item.size > Number( file.dataset.bytes );
				} );
				error.textContent = files.length > Number( file.dataset.max ) ? file.dataset.errorCount : ( bad ? file.dataset.errorFile : '' );
				preview.innerHTML = '';
				if ( error.textContent ) {
					file.value = '';
					return;
				}
				files.forEach( function ( item ) {
					var img = doc.createElement( 'img' );
					img.alt = '';
					img.src = window.URL.createObjectURL( item );
					preview.appendChild( img );
				} );
			} );
		}

		all( '[data-dw-write-review]' ).forEach( function ( link ) {
			link.addEventListener( 'click', function () {
				window.setTimeout( function () {
					var target = doc.getElementById( 'rating' ) || doc.getElementById( 'comment' );
					if ( target ) {
						target.focus( { preventScroll: true } );
					}
				}, 50 );
			} );
		} );

		var box = null;
		var group = [];
		var index = 0;
		var opener = null;

		function show( next ) {
			index = ( next + group.length ) % group.length;
			var img = box.querySelector( 'img' );
			img.src = group[ index ].href;
			img.alt = group[ index ].getAttribute( 'aria-label' );
			all( '.dw-lightbox__arrow', box ).forEach( function ( arrow ) {
				arrow.hidden = group.length < 2;
			} );
		}

		function closeBox() {
			box.hidden = true;
			doc.documentElement.classList.remove( 'dw-nl-lock' );
			if ( opener ) {
				opener.focus();
			}
		}

		doc.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[data-dw-lightbox]' );
			if ( ! link ) {
				return;
			}
			event.preventDefault();
			if ( ! box ) {
				box = doc.createElement( 'div' );
				box.className = 'dw-lightbox';
				box.setAttribute( 'role', 'dialog' );
				box.setAttribute( 'aria-modal', 'true' );
				box.setAttribute( 'aria-label', link.closest( '.dw-review-photos' ).dataset.label || 'Photo' );
				box.innerHTML = '<img alt=""><button type="button" class="dw-btn dw-btn--icon dw-lightbox__close" data-box="close" aria-label="Close">' + '<svg class="dw-icon" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" aria-hidden="true"><path d="m3.5 3.5 9 9m0-9-9 9"/></svg></button>' +
					'<button type="button" class="dw-btn dw-btn--icon dw-lightbox__arrow dw-lightbox__arrow--prev" data-box="-1" aria-label="Previous"><svg class="dw-icon" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" aria-hidden="true"><path d="M10 3.5 5.5 8l4.5 4.5"/></svg></button>' +
					'<button type="button" class="dw-btn dw-btn--icon dw-lightbox__arrow dw-lightbox__arrow--next" data-box="1" aria-label="Next"><svg class="dw-icon" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5"/></svg></button>';
				doc.body.appendChild( box );
				box.addEventListener( 'click', function ( e ) {
					var action = e.target.closest( '[data-box]' );
					if ( action && 'close' !== action.dataset.box ) {
						show( index + Number( action.dataset.box ) );
					} else if ( action || e.target === box ) {
						closeBox();
					}
				} );
				box.addEventListener( 'keydown', function ( e ) {
					if ( 'Escape' === e.key ) {
						closeBox();
					} else if ( 'ArrowRight' === e.key || 'ArrowLeft' === e.key ) {
						show( index + ( 'ArrowRight' === e.key ? 1 : -1 ) );
					}
					trapFocus( e, box );
				} );
			}
			opener = link;
			group = all( 'a[data-dw-lightbox="' + link.dataset.dwLightbox + '"]' );
			box.hidden = false;
			doc.documentElement.classList.add( 'dw-nl-lock' );
			show( group.indexOf( link ) );
			box.querySelector( '.dw-lightbox__close' ).focus();
		} );
	}

	/* Recently viewed: product IDs kept in the browser, cards fetched on demand. */
	function initRecent() {
		var KEY = 'dw_recent';
		var ids = ( store( KEY ) || [] ).filter( function ( id ) {
			return id > 0;
		} );
		var page = doc.querySelector( '.dw-pdp[data-product-id]' );
		if ( page ) {
			var current = Number( page.dataset.productId );
			ids = [ current ].concat( ids.filter( function ( id ) {
				return id !== current;
			} ) ).slice( 0, 9 );
			store( KEY, ids );
		}
		if ( ! window.fetch ) {
			return;
		}
		all( '.dw-recent' ).forEach( function ( section ) {
			var exclude = Number( section.dataset.exclude );
			var list = ids.filter( function ( id ) {
				return id !== exclude;
			} ).slice( 0, 8 );
			if ( ! list.length ) {
				return;
			}
			getJSON( section.dataset.endpoint + ( section.dataset.endpoint.indexOf( '?' ) > -1 ? '&' : '?' ) + 'ids=' + list.join( ',' ) ).then( function ( data ) {
				if ( ! data.html ) {
					return;
				}
				var body = section.querySelector( '.dw-recent__body' );
				body.innerHTML = data.html;
				section.hidden = false;
				all( '.dw-carousel', body ).forEach( initCarousel );
				syncCardPrices( body );
			} ).catch( function () {} );
			section.querySelector( '[data-recent-clear]' ).addEventListener( 'click', function () {
				store( KEY, page ? [ Number( page.dataset.productId ) ] : null );
				section.hidden = true;
			} );
		} );
	}

	/* Routine quiz: one question at a time, then product picks. */
	function initQuiz( quiz ) {
		var intro = quiz.querySelector( '.dw-quiz__intro' );
		var form = quiz.querySelector( '.dw-quiz__form' );
		var result = quiz.querySelector( '.dw-quiz__result' );
		var products = quiz.querySelector( '.dw-quiz__products' );
		var error = quiz.querySelector( '.dw-quiz__error' );
		var steps = all( '.dw-quiz__step', form );
		var back = form.querySelector( '[data-quiz-back]' );
		var next = form.querySelector( '[data-quiz-next]' );
		var bar = form.querySelector( '.dw-quiz__progress span' );
		var current = 0;

		function answered( step ) {
			return !! step.querySelector( 'input:checked' );
		}

		function go( index ) {
			current = index;
			steps.forEach( function ( step, i ) {
				step.hidden = i !== index;
			} );
			bar.style.width = ( ( index + 1 ) / steps.length * 100 ) + '%';
			back.style.visibility = index ? 'visible' : 'hidden';
			next.disabled = ! answered( steps[ index ] );
			next.querySelector( 'span' ).textContent = index === steps.length - 1 ? next.dataset.finish : next.dataset.next;
			steps[ index ].querySelector( '.dw-quiz__q' ).focus( { preventScroll: true } );
		}

		function finish() {
			var query = steps.map( function ( step ) {
				var input = step.querySelector( 'input:checked' );
				return encodeURIComponent( input.name ) + '=' + encodeURIComponent( input.value );
			} ).join( '&' );
			form.hidden = true;
			result.hidden = false;
			error.hidden = true;
			products.innerHTML = '';
			products.setAttribute( 'aria-busy', 'true' );
			quiz.querySelector( '[data-quiz-summary]' ).textContent = '';
			result.querySelector( '.dw-quiz__title' ).focus( { preventScroll: true } );
			quiz.scrollIntoView( { block: 'start', behavior: reduceMotion ? 'auto' : 'smooth' } );
			getJSON( quiz.dataset.endpoint + ( quiz.dataset.endpoint.indexOf( '?' ) > -1 ? '&' : '?' ) + query ).then( function ( data ) {
				quiz.querySelector( '[data-quiz-summary]' ).textContent = data.text;
				products.innerHTML = data.html;
				syncCardPrices( products );
			} ).catch( function () {
				error.hidden = false;
			} ).then( function () {
				products.setAttribute( 'aria-busy', 'false' );
			} );
		}

		quiz.querySelector( '[data-quiz-start]' ).addEventListener( 'click', function () {
			intro.hidden = true;
			form.hidden = false;
			go( 0 );
		} );

		form.addEventListener( 'change', function () {
			next.disabled = ! answered( steps[ current ] );
		} );

		// A pointer click moves on by itself; keyboard users confirm with Next.
		form.addEventListener( 'click', function ( event ) {
			var input = event.target.closest( 'input[type="radio"]' );
			if ( input && event.detail > 0 ) {
				window.setTimeout( function () {
					form.requestSubmit ? form.requestSubmit( next ) : next.click();
				}, reduceMotion ? 0 : 200 );
			}
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			if ( ! answered( steps[ current ] ) ) {
				return;
			}
			if ( current < steps.length - 1 ) {
				go( current + 1 );
			} else {
				finish();
			}
		} );

		back.addEventListener( 'click', function () {
			if ( current > 0 ) {
				go( current - 1 );
			}
		} );

		quiz.querySelector( '[data-quiz-restart]' ).addEventListener( 'click', function () {
			form.reset();
			result.hidden = true;
			form.hidden = false;
			go( 0 );
		} );
	}

	function init() {
		initPromo();
		initHeader();
		all( '.dw-carousel' ).forEach( initCarousel );
		initCardOptions();
		initBag();
		initProduct();
		initHelp();
		initNewsletter();
		initReviews();
		initRecent();
		all( '.dw-quiz' ).forEach( initQuiz );
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

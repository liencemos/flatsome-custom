<?php

function vertuo_rebind_footer_form( $old_id, $new_id ) {
	$footer = get_page_by_path( 'vertuo-footer', OBJECT, array( 'blocks' ) );
	if ( ! $footer ) {
		return;
	}

	$content = preg_replace_callback( '/' . get_shortcode_regex( array( 'contact-form-7' ) ) . '/', static function ( $match ) use ( $old_id, $new_id ) {
		if ( '[' === $match[1] && ']' === $match[6] ) {
			return $match[0];
		}
		$attributes = shortcode_parse_atts( $match[3] );
		if ( (string) $old_id !== ( $attributes['id'] ?? '' ) ) {
			return $match[0];
		}
		preg_match_all( get_shortcode_atts_regex(), $match[3], $parts, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL );
		foreach ( array_reverse( $parts ) as $part ) {
			foreach ( array( 1, 3, 5 ) as $key ) {
				if ( 0 === strcasecmp( $part[ $key ][0] ?? '', 'id' ) ) {
					$value = $part[ $key + 1 ];
					return substr_replace( $match[0], (string) $new_id, 1 + strlen( $match[1] . $match[2] ) + $value[1], strlen( $value[0] ) );
				}
			}
		}
		return $match[0];
	}, $footer->post_content );

	if ( $content !== $footer->post_content ) {
		$result = wp_update_post( wp_slash( array( 'ID' => $footer->ID, 'post_content' => $content ) ), true );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
	}
}

function vertuo_seed_form( $key, $title, $fields, $mail_body ) {
	$option = 'vertuo_form_' . $key;
	$id = (int) get_option( $option );
	if ( $id && WPCF7_ContactForm::get_instance( $id ) ) {
		return $id;
	}

	$form = WPCF7_ContactForm::get_template( array( 'title' => $title, 'locale' => 'en_US' ) );
	$mail = $form->prop( 'mail' );
	$mail['subject'] = $title . ' — [_site_title]';
	$mail['recipient'] = '[_site_admin_email]';
	$mail['body'] = $mail_body;
	$mail['additional_headers'] = 'Reply-To: [your-email]';
	$form->set_properties( array( 'form' => $fields, 'mail' => $mail ) );
	$form->save();
	if ( ! $form->id() ) {
		WP_CLI::error( 'Could not create the ' . $title . ' form.' );
	}
	if ( $id ) {
		vertuo_rebind_footer_form( $id, $form->id() );
	}
	update_option( $option, $form->id() );
	return $form->id();
}

$contact_form = <<<'FORM'
<div class="vertuo-contact-fields">
<div><label> Name (required) [text* your-name autocomplete:name] </label></div>
<div><label> Email (required) [email* your-email autocomplete:email] </label></div>
<div><label> Phone (optional) [tel your-phone autocomplete:tel] </label></div>
<div><label> Order number (optional) [text order-number] </label></div>
<div class="vertuo-field-wide"><label> Topic (required) [select* topic first_as_label "Choose a topic" "Orders and delivery" "Returns and product issues" "Pods and compatibility" "Accessibility" "Something else"] </label></div>
<div class="vertuo-field-wide"><label> Message (required) [textarea* your-message] </label></div>
</div>
[submit "Send message"]
FORM;

vertuo_seed_form( 'contact', 'Contact Us', $contact_form, "Name: [your-name]\nEmail: [your-email]\nPhone: [your-phone]\nOrder: [order-number]\nTopic: [topic]\n\n[your-message]" );
vertuo_seed_form( 'newsletter', 'Newsletter Signup', '<label> Email address [email* your-email autocomplete:email] </label>[submit "Sign up"]', 'Please add [your-email] to the Vertuo mailing list.' );

$pages = array(
	'contact-us' => array(
		'Contact Us',
		<<<'HTML'
<p>Have a question about pods, delivery, or an order? Start with one of the options below or send us a message.</p>
<div class="vertuo-contact-options">
<article><h2>Find an answer</h2><p>Check compatibility, pack sizes, and common order questions.</p>[vertuo_service_link page="help-center"]</article>
<article><h2>Check an order</h2><p>Look up your order using its number and billing email.</p>[vertuo_service_link page="track-order"]</article>
<article><h2>Send a message</h2><p>Use the form below for questions we haven't covered.</p><a href="#send-a-message">Go to contact form</a></article>
</div>
<section id="send-a-message" class="vertuo-contact-panel">
<h2>Send us a message</h2>
<p>Please don't include payment card details or passwords.</p>
[vertuo_contact_form]
</section>
HTML
	),
	'help-center' => array(
		'Help Center',
		<<<'HTML'
<p>Quick answers about Vertuo pods and shopping with us.</p>
<h2>Do Vertuo pods work in Original machines?</h2>
<p>No. Vertuo pods are for Vertuo machines. Check each product's compatibility notes; certain larger formats require specific models.</p>
<h2>What do the pack sizes mean?</h2>
<p>Most coffees come in packs of 50, 100, 150, or 200 pods. Choose a size on the product page to see its price. Bundles list their own contents.</p>
<h2>Where can I find my order details?</h2>
<p>Use the order number in your confirmation email and the billing email you used at checkout.</p>
[vertuo_service_link page="track-order"]
HTML
	),
	'shipping' => array(
		'Shipping',
		<<<'HTML'
<p>Enter your delivery address at checkout to see the available shipping methods, costs, and any delivery estimates.</p>
<h2>After you place your order</h2>
<p>Keep your confirmation email for your records. If carrier tracking is available, use the tracking link in your shipping confirmation.</p>
<h2>Address changes and delays</h2>
<p>If your address is incorrect or a delivery is late, contact us with your order number. Changes may not be possible once a parcel has shipped.</p>
[vertuo_service_link page="contact-us"]
HTML
	),
	'returns-and-warranty' => array(
		'Returns & Warranty',
		<<<'HTML'
<p>If an order arrives damaged or an item is missing or incorrect, send us your order number and a description of the issue. Keep the packaging while we review your request.</p>
<h2>Before returning an item</h2>
<p>Ask us to confirm eligibility and give you return instructions before mailing anything back. Eligibility may depend on whether a product has been opened and on the terms that applied to your order.</p>
<h2>Accessory warranties</h2>
<p>If an accessory includes a manufacturer warranty, check the documentation supplied with it or contact us for help finding the right next step.</p>
[vertuo_service_link page="contact-us"]
HTML
	),
	'track-order' => array(
		'Track Your Order',
		<<<'HTML'
<p>Enter the order number from your confirmation email and the billing email used at checkout.</p>
[woocommerce_order_tracking]
HTML
	),
	'about-vertuo' => array(
		'About Vertuo',
		<<<'HTML'
<p>Our catalog brings together Vertuo coffees, bundles, and accessories. Explore by cup size, flavor, or collection to find something that fits the way you brew.</p>
<h2>Compare before you buy</h2>
<p>Product pages show the original tasting notes and specifications where available. Check the listed pod format and machine compatibility before choosing a pack.</p>
HTML
	),
	'store-privacy' => array(
		'Privacy Policy',
		<<<'HTML'
<p>This page summarizes the information used to run the Vertuo storefront.</p>
<h2>Orders and accounts</h2>
<p>The store uses the information you provide at checkout to process orders, arrange delivery, and help with support requests. Cookies can keep your cart and sign-in session working.</p>
<h2>Contact and newsletter requests</h2>
<p>We use what you send to respond to your message or handle your mailing list request. You can contact us with privacy questions or to ask about your information.</p>
[vertuo_service_link page="contact-us"]
HTML
	),
	'terms-of-use' => array(
		'Terms of Use',
		<<<'HTML'
<p>Please check the items, quantities, availability, and total shown at checkout before placing an order.</p>
<h2>Product information</h2>
<p>Product pages describe pod sizes, compatible machines, and available pack options. Contact us before ordering if you're not sure whether a product fits your machine.</p>
<h2>Order questions</h2>
<p>Keep your confirmation email. If there is an issue with your order, contact us with its number so we can review it.</p>
[vertuo_service_link page="contact-us"]
HTML
	),
	'accessibility' => array(
		'Accessibility',
		<<<'HTML'
<p>We want this store to be usable for everyone. If a page, product detail, or checkout step is hard to use, tell us what happened and include the page address if you can.</p>
<p>You can select Accessibility in the support form so we can help you find the information or complete the task.</p>
[vertuo_service_link page="contact-us"]
HTML
	),
);

foreach ( $pages as $slug => list( $title, $body ) ) {
	if ( get_page_by_path( $slug, OBJECT, array( 'page' ) ) ) {
		continue;
	}
	$id = wp_insert_post( wp_slash( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'post_name'      => $slug,
		'post_title'     => $title,
		'post_content'   => '<div class="container vertuo-service-page"><h1>' . esc_html( $title ) . '</h1>' . "\n" . $body . "\n</div>",
		'page_template'  => 'page-blank.php',
		'comment_status' => 'closed',
	) ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
}

WP_CLI::success( 'Contact and newsletter forms and customer-service pages are available.' );

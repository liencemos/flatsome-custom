<?php
/**
 * Sample questions for the Dewier FAQ widget: array( topic, question, answer ).
 * They are placeholders for the shop's own policies.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dewier_contact = '<a href="' . esc_url( home_url( '/contact/' ) ) . '">' . __( 'contact us', 'dewier' ) . '</a>';
$dewier_returns = '<a href="' . esc_url( home_url( '/shipping-returns/' ) ) . '">' . __( 'Returns & exchanges', 'dewier' ) . '</a>';

return array(
	array( __( 'Shipping', 'dewier' ), __( 'When will my order arrive?', 'dewier' ), '<p>' . __( 'We pack orders within <strong>1 to 2 business days</strong>. Standard shipping then takes 3 to 7 business days.', 'dewier' ) . '</p><p>' . __( 'You will get an email with a tracking link as soon as your package leaves us.', 'dewier' ) . '</p>' ),
	array( __( 'Shipping', 'dewier' ), __( 'How much does shipping cost?', 'dewier' ), '<p>' . __( 'Standard shipping is $5. Orders of $40 or more, after discounts, ship free.', 'dewier' ) . '</p>' ),
	array( __( 'Shipping', 'dewier' ), __( 'Where do you ship?', 'dewier' ), '<p>' . __( 'We currently ship within the United States.', 'dewier' ) . '</p>' ),
	array( __( 'Shipping', 'dewier' ), __( 'Do you ship to PO Boxes?', 'dewier' ), '<p>' . __( 'Yes. PO Box orders go out with standard shipping.', 'dewier' ) . '</p>' ),
	array( __( 'Shipping', 'dewier' ), __( 'My tracking details are not working. Can you help?', 'dewier' ), '<p>' . __( 'Tracking can take up to 48 hours to update after you receive your shipping email.', 'dewier' ) . '</p><p>' . sprintf( /* translators: %s: contact link */ __( 'If nothing has changed after that, %s with your order number and we will look into it.', 'dewier' ), $dewier_contact ) . '</p>' ),
	array( __( 'Shipping', 'dewier' ), __( 'How can I edit my shipping address?', 'dewier' ), '<p>' . sprintf( /* translators: %s: contact link */ __( 'Please %s as soon as you can. We can update the address until the order has been packed.', 'dewier' ), $dewier_contact ) . '</p>' ),

	array( __( 'Products', 'dewier' ), __( 'How can I find out more about a product?', 'dewier' ), '<p>' . __( 'Each product page lists what the product does, how to use it and its full ingredient list.', 'dewier' ) . '</p>' ),
	array( __( 'Products', 'dewier' ), __( 'How do I choose the right shade?', 'dewier' ), '<p>' . __( 'Pick a shade on the product page to see its swatch and name. If you are between two shades, the lighter one is usually the safer choice for sheer formulas.', 'dewier' ) . '</p>' ),
	array( __( 'Products', 'dewier' ), __( 'Are your products suitable for my skin type?', 'dewier' ), '<p>' . __( 'Our formulas are made for all skin types. If your skin is sensitive, check the ingredient list on the product page and patch test before first use.', 'dewier' ) . '</p>' ),
	array( __( 'Products', 'dewier' ), __( 'How should I store my products?', 'dewier' ), '<p>' . __( 'Keep them closed, at room temperature and out of direct sunlight. The open-jar symbol on the packaging shows how many months a product lasts once opened.', 'dewier' ) . '</p>' ),
	array( __( 'Products', 'dewier' ), __( 'An item is sold out. When will it be back?', 'dewier' ), '<p>' . sprintf( /* translators: %s: contact link */ __( 'Restock dates vary by product. You can %s and we will tell you what we know.', 'dewier' ), $dewier_contact ) . '</p>' ),
	array( __( 'Products', 'dewier' ), __( 'Can I sell your products in my own store?', 'dewier' ), '<p>' . __( 'We do not offer wholesale at this time.', 'dewier' ) . '</p>' ),

	array( __( 'Returns & Exchanges', 'dewier' ), __( 'What is your return policy?', 'dewier' ), '<p>' . sprintf( /* translators: %s: returns page link */ __( 'You can return any item within 30 days of delivery for a refund. The details are on our %s page.', 'dewier' ), $dewier_returns ) . '</p>' ),
	array( __( 'Returns & Exchanges', 'dewier' ), __( 'How do I place a return or exchange?', 'dewier' ), '<p>' . sprintf( /* translators: %s: contact link */ __( 'To start, %s with your order number and the items you would like to return or exchange. We will reply with the next steps.', 'dewier' ), $dewier_contact ) . '</p>' ),
	array( __( 'Returns & Exchanges', 'dewier' ), __( 'How long does it take to receive a refund?', 'dewier' ), '<p>' . __( 'We issue refunds to the original payment method within 5 business days of receiving your return. Your bank may need a few more days to show it.', 'dewier' ) . '</p>' ),
	array( __( 'Returns & Exchanges', 'dewier' ), __( 'What should I do if I receive the wrong product?', 'dewier' ), '<p>' . sprintf( /* translators: %s: contact link */ __( 'We are sorry about that. Please %s with your order number and a photo of what arrived, and we will make it right.', 'dewier' ), $dewier_contact ) . '</p>' ),
	array( __( 'Returns & Exchanges', 'dewier' ), __( 'Can I return a product I received as a gift?', 'dewier' ), '<p>' . __( 'Yes. We will need the order number or the email address of the person who placed the order.', 'dewier' ) . '</p>' ),

	array( __( 'Orders', 'dewier' ), __( 'Can I change or cancel my order?', 'dewier' ), '<p>' . sprintf( /* translators: %s: contact link */ __( 'We start packing quickly, so please %s right away. Once an order has been packed we can no longer change it.', 'dewier' ), $dewier_contact ) . '</p>' ),
	array( __( 'Orders', 'dewier' ), __( 'I did not receive an order confirmation. What now?', 'dewier' ), '<p>' . __( 'Check your spam folder first. If it is not there, the email address on the order may have a typo.', 'dewier' ) . '</p><p>' . sprintf( /* translators: %s: contact link */ __( 'Please %s and we will find your order.', 'dewier' ), $dewier_contact ) . '</p>' ),
	array( __( 'Orders', 'dewier' ), __( 'Can I add an item to an order I already placed?', 'dewier' ), '<p>' . __( 'We cannot add items to an existing order. Please place a new order for anything you would like to add.', 'dewier' ) . '</p>' ),
	array( __( 'Orders', 'dewier' ), __( 'Do I need an account to place an order?', 'dewier' ), '<p>' . __( 'No. You can check out as a guest with just your email address.', 'dewier' ) . '</p>' ),
	array( __( 'Orders', 'dewier' ), __( 'An item is missing from my order. What should I do?', 'dewier' ), '<p>' . sprintf( /* translators: %s: contact link */ __( 'Please %s with your order number and the missing item, and we will sort it out.', 'dewier' ), $dewier_contact ) . '</p>' ),

	array( __( 'Billing', 'dewier' ), __( 'Which payment methods do you accept?', 'dewier' ), '<p>' . __( 'Every payment method we accept is shown at checkout.', 'dewier' ) . '</p>' ),
	array( __( 'Billing', 'dewier' ), __( 'When will I be charged?', 'dewier' ), '<p>' . __( 'Your payment is taken when you place your order.', 'dewier' ) . '</p>' ),
	array( __( 'Billing', 'dewier' ), __( 'Why was my payment declined?', 'dewier' ), '<p>' . __( 'Check that the card number, expiry date, security code and billing address match what your bank has on file. If it is still declined, your bank can tell you why.', 'dewier' ) . '</p>' ),
	array( __( 'Billing', 'dewier' ), __( 'How do I use a promo code?', 'dewier' ), '<p>' . __( 'Enter the code in the coupon field in your bag or at checkout. Only one code can be used per order.', 'dewier' ) . '</p>' ),
	array( __( 'Billing', 'dewier' ), __( 'Is sales tax included in the price?', 'dewier' ), '<p>' . __( 'Any sales tax is calculated from your shipping address and shown at checkout before you pay.', 'dewier' ) . '</p>' ),

	array( __( 'Contact', 'dewier' ), __( 'I have a question that is not listed here. How do I reach you?', 'dewier' ), '<p>' . sprintf( /* translators: %s: contact link */ __( 'We are happy to help. Please %s and a person will get back to you.', 'dewier' ), $dewier_contact ) . '</p>' ),
	array( __( 'Contact', 'dewier' ), __( 'How quickly will I get a reply?', 'dewier' ), '<p>' . __( 'We reply to most messages within 2 business days.', 'dewier' ) . '</p>' ),

	array( __( 'Code Of Conduct', 'dewier' ), __( 'What is the Dewier Code of Conduct?', 'dewier' ), '<p>' . __( 'We treat every customer with respect and ask for the same in return. We may end a conversation or decline service when messages to our team are abusive or threatening.', 'dewier' ) . '</p>' ),
);

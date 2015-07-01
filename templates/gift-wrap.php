<div class="gift-wrapping" style="clear:both; padding-top: .5em;">
	<label><?php echo str_replace( array( '{checkbox}', '{price}' ), array( $checkbox_gift, $price_text ), wp_kses_post( $product_gift_wrap_message ) ); ?></label>
    <div class="extra-fields-container unchecked">
        <label><?php echo str_replace( array( '{checkbox_note}', '{price_note}' ), array( $checkbox_note, $price_note_text ), wp_kses_post( $product_gift_note_message ) ); ?></label>
    </div>
</div>
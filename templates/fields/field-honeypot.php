<?php
// Honeypot fields are hidden from users but kept for compatibility.
?>
<div class="satori-field satori-field--honeypot" style="display:none;">
    <label><?php echo esc_html( $field['label'] ?? '' ); ?></label>
    <input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" value="" tabindex="-1" autocomplete="off" />
</div>

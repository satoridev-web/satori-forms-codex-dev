<?php
?>
<div class="satori-field satori-field--submit">
    <button type="submit" class="satori-form__submit <?php echo esc_attr( $field['css_class'] ?? '' ); ?>">
        <?php echo esc_html( $field['label'] ?? __( 'Submit', 'satori-forms' ) ); ?>
    </button>
</div>

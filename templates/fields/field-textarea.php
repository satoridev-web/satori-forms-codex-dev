<?php
$field_id = 'satori-field-' . esc_attr( $field['id'] );
$classes  = array( 'satori-field', 'satori-field--textarea', $field['wrapper_class'] ?? '' );
$attrs    = '';
if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
    foreach ( $field['attributes'] as $attr_key => $attr_value ) {
        $attrs .= sprintf( ' %s="%s"', esc_attr( $attr_key ), esc_attr( $attr_value ) );
    }
}
?>
<div class="<?php echo esc_attr( trim( implode( ' ', array_filter( $classes ) ) ) ); ?>">
    <?php if ( ! empty( $field['label'] ) ) : ?>
        <label class="satori-field__label" for="<?php echo esc_attr( $field_id ); ?>">
            <?php echo esc_html( $field['label'] ); ?>
            <?php if ( ! empty( $field['required'] ) ) : ?>
                <span class="satori-field__required" aria-hidden="true">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>
    <textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" class="satori-field__control <?php echo esc_attr( $field['css_class'] ?? '' ); ?>" rows="<?php echo isset( $field['attributes']['rows'] ) ? intval( $field['attributes']['rows'] ) : 4; ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?><?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_textarea( $value ); ?></textarea>
    <?php if ( ! empty( $field['help_text'] ) ) : ?>
        <p class="satori-field__help"><?php echo esc_html( $field['help_text'] ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $errors ) ) : ?>
        <ul class="satori-field__errors">
            <?php foreach ( $errors as $error ) : ?>
                <li><?php echo esc_html( $error ); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
$field_id = 'satori-field-' . esc_attr( $field['id'] );
$classes  = array( 'satori-field', 'satori-field--select', $field['wrapper_class'] ?? '' );
$options  = is_array( $field['options'] ?? null ) ? $field['options'] : array();
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
    <select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" class="satori-field__control <?php echo esc_attr( $field['css_class'] ?? '' ); ?>" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
        <?php if ( ! empty( $field['placeholder'] ) ) : ?>
            <option value="" disabled <?php selected( '', $value ); ?>><?php echo esc_html( $field['placeholder'] ); ?></option>
        <?php endif; ?>
        <?php foreach ( $options as $option_value => $label ) : ?>
            <?php if ( is_numeric( $option_value ) ) {
                $option_value = $label;
            }
            ?>
            <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select>
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

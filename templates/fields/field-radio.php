<?php
$field_id = 'satori-field-' . esc_attr( $field['id'] );
$classes  = array( 'satori-field', 'satori-field--radio', $field['wrapper_class'] ?? '' );
$options  = is_array( $field['options'] ?? null ) ? $field['options'] : array();
?>
<div class="<?php echo esc_attr( trim( implode( ' ', array_filter( $classes ) ) ) ); ?>">
    <?php if ( ! empty( $field['label'] ) ) : ?>
        <span class="satori-field__label"><?php echo esc_html( $field['label'] ); ?><?php if ( ! empty( $field['required'] ) ) : ?><span class="satori-field__required" aria-hidden="true">*</span><?php endif; ?></span>
    <?php endif; ?>
    <div class="satori-field__choices">
        <?php foreach ( $options as $option_value => $label ) :
            if ( is_numeric( $option_value ) ) {
                $option_value = $label;
            }
            $id = $field_id . '-' . sanitize_title( $option_value );
            ?>
            <label for="<?php echo esc_attr( $id ); ?>" class="satori-field__choice">
                <input type="radio" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $value, $option_value ); ?> <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?> />
                <span><?php echo esc_html( $label ); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
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

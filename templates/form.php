<?php
/**
 * Main form wrapper template.
 *
 * @var WP_Post                               $form
 * @var array                                 $fields
 * @var array                                 $settings
 * @var array                                 $errors
 * @var array                                 $values
 * @var bool                                  $success
 * @var \Satori\Forms\Frontend\Form_Renderer $renderer
 */
?>
<div class="satori-form" data-form-id="<?php echo esc_attr( $form_id ); ?>">
    <?php if ( $success ) : ?>
        <?php $renderer->render_success( $success_message ); ?>
    <?php else : ?>
        <?php $renderer->render_errors( $errors ); ?>
        <form class="satori-form__form" method="post" enctype="multipart/form-data">
            <?php do_action( 'satori_forms_form_start', $form_id ); ?>
            <input type="hidden" name="satori_form_id" value="<?php echo esc_attr( $form_id ); ?>" />
            <input type="hidden" name="_satori_forms_nonce" value="<?php echo esc_attr( wp_create_nonce( 'satori_form_' . $form_id ) ); ?>" />
            <?php if ( ! empty( $settings['honeypot_enabled'] ) ) : ?>
                <div class="satori-form__honeypot" aria-hidden="true">
                    <label><?php esc_html_e( 'Leave this field empty', 'satori-forms' ); ?></label>
                    <input type="text" name="satori_hp" value="" tabindex="-1" autocomplete="off" />
                </div>
            <?php endif; ?>
            <?php if ( ! empty( $settings['timestamp_enabled'] ) ) : ?>
                <input type="hidden" name="satori_ts" value="<?php echo esc_attr( time() ); ?>" />
            <?php endif; ?>
            <?php foreach ( $fields as $field ) : ?>
                <?php
                /**
                 * Fires before a field is rendered.
                 */
                do_action( 'satori_forms_before_field', $field, $form_id );
                $renderer->render_field( $field, $values, $errors );
                do_action( 'satori_forms_after_field', $field, $form_id );
                ?>
            <?php endforeach; ?>
            <div class="satori-form__actions">
                <button type="submit" class="satori-form__submit"><?php esc_html_e( 'Submit', 'satori-forms' ); ?></button>
            </div>
            <?php do_action( 'satori_forms_form_end', $form_id ); ?>
        </form>
    <?php endif; ?>
</div>

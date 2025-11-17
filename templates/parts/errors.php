<?php
if ( empty( $errors ) ) {
    return;
}
$messages = array();
foreach ( $errors as $key => $error_group ) {
    foreach ( (array) $error_group as $message ) {
        $messages[] = $message;
    }
}
if ( empty( $messages ) ) {
    return;
}
?>
<div class="satori-form__errors" role="alert">
    <ul>
        <?php foreach ( $messages as $message ) : ?>
            <li><?php echo esc_html( $message ); ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php
namespace Satori\Forms\Meta;

use Satori\Forms\Post_Types\Form_Submission_Post_Type;

/**
 * Displays submission meta.
 */
class Submission_Meta {
    const META_FORM_ID  = '_satori_forms_submission_form_id';
    const META_PAYLOAD  = '_satori_forms_submission_payload';
    const META_META     = '_satori_forms_submission_meta';
    const META_STATUS   = '_satori_forms_submission_status';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_filter( 'manage_' . Form_Submission_Post_Type::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
        add_action( 'manage_' . Form_Submission_Post_Type::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box( 'satori-forms-submission-details', __( 'Submission Details', 'satori-forms' ), array( $this, 'render_details' ), Form_Submission_Post_Type::POST_TYPE, 'normal', 'high' );
    }

    /**
     * Render submission payload.
     *
     * @param \WP_Post $post Post.
     */
    public function render_details( $post ) {
        $payload = get_post_meta( $post->ID, self::META_PAYLOAD, true );
        $meta    = get_post_meta( $post->ID, self::META_META, true );

        echo '<h4>' . esc_html__( 'Payload', 'satori-forms' ) . '</h4>';
        echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;overflow:auto;">' . esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT ) ) . '</pre>';
        echo '<h4>' . esc_html__( 'Meta', 'satori-forms' ) . '</h4>';
        echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;overflow:auto;">' . esc_html( wp_json_encode( $meta, JSON_PRETTY_PRINT ) ) . '</pre>';
    }

    /**
     * Adjust admin columns.
     *
     * @param array $columns Columns.
     *
     * @return array
     */
    public function columns( $columns ) {
        $columns['satori_form'] = __( 'Form', 'satori-forms' );
        $columns['satori_ip']   = __( 'IP Address', 'satori-forms' );
        $columns['satori_status'] = __( 'Status', 'satori-forms' );
        return $columns;
    }

    /**
     * Render custom column content.
     *
     * @param string $column Column name.
     * @param int    $post_id Post ID.
     */
    public function column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'satori_form':
                $form_id = get_post_meta( $post_id, self::META_FORM_ID, true );
                if ( $form_id ) {
                    echo '<a href="' . esc_url( get_edit_post_link( $form_id ) ) . '">' . esc_html( get_the_title( $form_id ) ) . '</a>';
                }
                break;
            case 'satori_ip':
                $meta = get_post_meta( $post_id, self::META_META, true );
                echo isset( $meta['ip'] ) ? esc_html( $meta['ip'] ) : '&mdash;';
                break;
            case 'satori_status':
                $status = get_post_meta( $post_id, self::META_STATUS, true );
                echo esc_html( $status ? $status : __( 'Received', 'satori-forms' ) );
                break;
        }
    }
}

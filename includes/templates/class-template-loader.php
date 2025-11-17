<?php
namespace Satori\Forms\Templates;

/**
 * Handles template lookup for frontend rendering.
 */
class Template_Loader {
    /**
     * Locate template path.
     *
     * @param string $template Template relative path.
     *
     * @return string
     */
    public function locate( $template ) {
        $template = ltrim( $template, '/' );
        $paths    = array(
            trailingslashit( get_stylesheet_directory() ) . 'satori-forms/' . $template,
            trailingslashit( get_template_directory() ) . 'satori-forms/' . $template,
            SATORI_FORMS_PATH . 'templates/' . $template,
        );

        foreach ( $paths as $path ) {
            if ( file_exists( $path ) ) {
                return $path;
            }
        }

        return '';
    }

    /**
     * Render template with context.
     *
     * @param string $template Template relative path.
     * @param array  $vars     Context.
     * @param bool   $echo     Whether to echo.
     *
     * @return string
     */
    public function render( $template, array $vars = array(), $echo = true ) {
        $path = $this->locate( $template );
        if ( ! $path ) {
            return '';
        }

        ob_start();
        extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract
        include $path;
        $content = ob_get_clean();

        if ( $echo ) {
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return $content;
    }
}

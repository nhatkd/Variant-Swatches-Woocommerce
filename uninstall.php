<?php
/**
 * Removes the swatch colours and images stored on attribute terms.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_metadata( 'term', 0, 'vsw_color', '', true );
delete_metadata( 'term', 0, 'vsw_image', '', true );

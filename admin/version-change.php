<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 11/01/2017
 * Time: 11:46 AM
 *
 * @since   1.0.5
 * @updated 1.0.5
 */

namespace WCP;

use function defined;

defined( 'ABSPATH' ) || exit;

/* Renames existing database options and metadata with the new version prefix */

$options = Wcp_Plugin::get_option_names( Wcp_Plugin::OLD_VERSION_OPTION_PREFIX );

foreach ( $options as $option ) {
	rename_option( Wcp_Plugin::OLD_VERSION_OPTION_PREFIX . $option,
	               Wcp_Plugin::OPTION_PREFIX . $option );
}

$metadataNames = Wcp_Plugin::get_metadata_names();
foreach ( $metadataNames as $metaType => $names ) {
	foreach ( $names as $name ) {
		rename_post_metadata( $metaType,
		                      Wcp_Plugin::OLD_VERSION_META_PREFIX . $name,
		                      Wcp_Plugin::META_PREFIX . $name );
	}
}

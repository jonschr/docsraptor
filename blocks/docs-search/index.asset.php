<?php
return array(
	'dependencies' => array(
		'wp-api-fetch',
		'wp-block-editor',
		'wp-blocks',
		'wp-components',
		'wp-element',
	),
	'version'      => file_exists( __DIR__ . '/index.js' ) ? (string) filemtime( __DIR__ . '/index.js' ) : DOCSRAPTOR_VERSION,
);

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'MDO_Reviews_Public' ) ) {
	MDO_Reviews_Public::render_current_store();
}

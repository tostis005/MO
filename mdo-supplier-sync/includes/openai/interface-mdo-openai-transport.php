<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface MDO_OpenAI_Transport_Interface {
	public function key(): string;
	public function is_configured( array $settings ): bool;
	public function test( array $settings ): array;
	public function upload( string $local_file, array $settings ): array;
}

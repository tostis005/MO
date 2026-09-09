<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface MDO_Feed_Provider_Interface {
	public function key(): string;
	public function file_extension(): string;
	public function columns(): array;
	public function transform( array $native_record ): array;
}

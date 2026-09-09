<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_OpenAI_Native_Provider implements MDO_Feed_Provider_Interface {
	private string $format;

	public function __construct( string $format = 'jsonl' ) {
		$this->format = in_array( $format, array( 'jsonl', 'csv', 'tsv' ), true ) ? $format : 'jsonl';
	}

	public function key(): string {
		return 'openai_native_' . $this->format;
	}

	public function file_extension(): string {
		return $this->format . '.gz';
	}

	public function columns(): array {
		return array(
			'item_id', 'group_id', 'listing_has_variations', 'variant_dict', 'offer_id',
			'title', 'description', 'url', 'brand', 'seller_name', 'marketplace_seller', 'seller_url',
			'image_url', 'additional_image_urls', 'availability', 'price', 'sale_price',
			'condition', 'product_category', 'weight', 'item_weight_unit', 'gtin', 'mpn',
			'shipping_price', 'shipping', 'accepts_returns', 'return_deadline_in_days', 'return_policy',
			'review_count', 'star_rating', 'is_eligible_search',
		);
	}

	public function transform( array $native_record ): array {
		unset( $native_record['_quality_warnings'] );
		if ( 'jsonl' === $this->format ) {
			return $native_record;
		}
		$row = array();
		foreach ( $this->columns() as $column ) {
			$value = $native_record[ $column ] ?? '';
			if ( 'additional_image_urls' === $column && is_array( $value ) ) {
				// The Stable OpenAI file-upload contract uses a comma-separated
				// URL list in delimited feeds; JSONL keeps a native array.
				$value = implode( ',', array_map( 'strval', $value ) );
			} elseif ( is_array( $value ) || is_object( $value ) ) {
				$value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			} elseif ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			$row[ $column ] = $value;
		}
		return $row;
	}
}

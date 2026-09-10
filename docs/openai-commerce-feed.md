# EMDO — OpenAI / ChatGPT Product Discovery feed

Last reviewed against the official OpenAI Agentic Commerce documentation: 2026-09-10.

## Scope

This integration extends the existing EMDO merchant-feed module. It does **not** create an unrelated standalone plugin and it does **not** enable Instant Checkout. Its job is to generate a high-quality Product Discovery catalog that sends the shopper back to `https://www.elmercadodeorigen.com/` for checkout.

The existing `mdo-chatgpt-discovery.php` MU-plugin remains separate. That module controls organic crawling/discovery (`OAI-SearchBot`, `ChatGPT-User` and `chatgpt-sitemap.xml`). A Product Feed is a separate merchant-onboarding mechanism and must not be confused with a public sitemap or robots rule.

## Specification change detected

**CAMBIO DE ESPECIFICACIÓN DETECTADO:** the current standard OpenAI-format File Upload documentation says standard uploads target the **US**. Row-level market fields do not change that. Additional markets and currencies must only be used after OpenAI confirms the integration and the allowed countries/currencies for the account.

EMDO therefore generates and validates the real Spanish/EUR catalog, but automated delivery remains disabled by default and is technically blocked until two explicit admin confirmations exist:

- direct Product Feed access has been granted by OpenAI;
- Spain/EUR has been confirmed for this integration.

Google-compatible mode is also blocked until OpenAI has explicitly confirmed and registered that compatibility path for the account.

## Official sources used

- Products: https://developers.openai.com/commerce/specs/file-upload/products
- File Upload overview: https://developers.openai.com/commerce/specs/file-upload/overview
- Commerce API overview: https://developers.openai.com/commerce/specs/api/overview
- Powering Product Discovery in ChatGPT: https://openai.com/index/powering-product-discovery-in-chatgpt/
- Shopping with ChatGPT Search: https://help.openai.com/en/articles/11128490-improved-shopping-results-from-chatgpt-search
- Merchant Feed Terms: https://openai.com/policies/merchant-feed-terms-of-service/

OpenAI can update these specifications. Re-check them before enabling a new transport, market, optional capability, or checkout feature.

## Architecture

The loader remains `mu-plugins/mdo-google-merchant-feeds-20260825.php` and now loads the existing Google module plus:

- `mdo-google-merchant/openai/core.php`: mapping, validation, full-snapshot generation, preview, scheduling and tombstones;
- `mdo-google-merchant/openai/transport.php`: encrypted credentials, SFTP delivery and a deliberately inactive API abstraction;
- `mdo-google-merchant/openai/admin.php`: `Feeds · OpenAI / ChatGPT` settings, status, preview, validation, generation, connection testing and explicit upload.

Generation and delivery are intentionally separate. A snapshot can be generated and QA'd without any OpenAI credentials.

## Native OpenAI feed

Primary format: `JSONL.gz`, UTF-8, one JSON object per line. Stable filename:

`mercado-de-origen-products.jsonl.gz`

Optional native CSV.gz and TSV.gz exports are available from the same mapper. The generator streams records to gzip instead of building the whole catalog in memory.

The OpenAI File Upload contract is a full snapshot. The integration keeps a manifest of previously exported item IDs. When an item disappears from the eligible current catalog, its prior record is retained for up to 14 days with `is_eligible_search=false`, matching the current OpenAI retention guidance and allowing deterministic delisting.

Default schedule: every 6 hours. Available schedules: manual, hourly, every 6 hours, every 12 hours, daily.

## Product and variation model

WooCommerce variable products are not exported as a single parent offer. Every published purchasable variation is independently mapped to one row with its own current price, stock, URL/image where available, and variant options.

Stable identity:

1. existing persisted `_mdo_openai_item_id`, if present;
2. SKU, only if that SKU is unique in WooCommerce;
3. simple product fallback: `wc-{product_id}`;
4. variation fallback: `wc-{parent_id}-v{variation_id}`.

The resolved ID is persisted in `_mdo_openai_item_id`, so later SKU edits cannot silently rewrite the OpenAI identity. Variable products use a separate persisted group ID `wcg-{parent_id}`. `group_id` must never equal an `item_id`.

`offer_id` is stable and based on seller identity plus the item ID; it never contains the current price.

## Required native fields

The mapper validates these current OpenAI discovery requirements:

- `item_id`
- `title`
- `description`
- `url`
- `brand`
- `seller_name`
- `image_url`
- `availability`
- `price`

URLs and image URLs are required to be absolute HTTPS values. Missing brand/image/price or duplicated IDs are validation errors and the bad row is not written as a valid offer.

## Seller, producer/brand and marketplace semantics

The production legal text in EMDO establishes the marketplace model: El Mercado de Origen is the intermediary/platform and, unless a product page expressly states otherwise, the seller identified on the product page is the contractual seller and fulfills the order.

Default OpenAI model is therefore:

- `seller_name`: real WCFM vendor/producer attached to the product;
- `brand`: real producer/brand from a supported taxonomy/meta field, with the WCFM store name as the final factual fallback;
- `marketplace_seller`: `El Mercado de Origen` **only** when OpenAI has explicitly configured that marketplace field for the account;
- `seller_url`: specific WCFM store URL when available.

No seller is inferred from words in a product title.

Admin also supports `El Mercado de Origen = seller_name` and a custom filter mode for future commercial structures, but the WCFM marketplace model is the project default.

Central brand function: `mdo_get_openai_brand( WC_Product $product )`.

## Descriptions and food attributes

HTML/shortcodes are stripped and whitespace is normalized. The mapper only enriches the factual description with data that actually exists in WooCommerce attributes/meta. Recognized food concepts include DOP/denomination, breed, feeding, seal, origin, producer, format and curing.

It does not create unsupported arbitrary OpenAI columns for those concepts. Information stays in the product description, category or `variant_dict` unless the official schema has an appropriate supported field.

A brand/vendor mismatch is a QA warning, not an automatic rewrite.

## Availability

Native mapping:

- Woo `instock` -> `in_stock`
- Woo `outofstock` -> `out_of_stock`
- Woo `onbackorder` -> `backorder`
- explicit `_mdo_openai_preorder=yes` -> `pre_order`
- unknown/unmapped -> `unknown`

A future availability date is never fabricated. If one is real, it can be supplied as `_mdo_openai_availability_date` or by the `mdo_openai_availability_date` filter.

Google-compatible mode maps native `pre_order` to `preorder`. Current Google-compatible OpenAI rules require `availability_date` for both `preorder` and `backorder`; the row is rejected if that date is absent.

## Price

The mapper uses WooCommerce display prices and the store currency, formatted as `amount CURRENCY`, e.g. `440.00 EUR`.

For a genuine current sale, the regular price is written as `price` and the current sale price as `sale_price`; validation requires the same currency and `0 < sale_price < price`.

No price is invented for products without a payable WooCommerce price.

## Images

Primary image order:

1. variation image;
2. parent product image.

Additional gallery images are emitted as `additional_image_urls` for native JSONL and mapped to the supported Google-compatible representation. URLs must be public HTTPS URLs.

## Weight and variants

`weight` and `item_weight_unit` are only emitted when WooCommerce contains an exact numeric weight using `g`, `kg`, `oz` or `lb`.

A textual weight range such as `7–8 kg` remains a variation option and is **not** converted into a fabricated exact product weight.

## Categories, GTIN and MPN

`product_category` reuses the EMDO WooCommerce product-category hierarchy (`Parent > Child`).

GTIN uses the existing EMDO checksum validator and only accepts valid 8/12/13/14-digit values. MPN is read only from known product metadata. No identifier is manufactured.

In Google-compatible mode, `identifier_exists=no` is emitted only when neither a valid GTIN nor MPN exists.

## Reviews

Only WooCommerce product review aggregates are used. Seller/store reviews are not mixed into `review_count` or `star_rating`. The count and average must describe the same population.

## Shipping

Shipping is disabled by default. Even if an admin selects a shipping representation, no shipping field is emitted unless `shipping_capability_confirmed=1` after OpenAI onboarding.

When enabled, the mapper reuses the real EMDO/WCFM shipping calculations already used by Google Merchant and can emit either:

- `shipping_price`; or
- the feed-specific `country:region:service_class:price` tuple.

It never emits both. Zero means free shipping only when the existing factual shipping logic resolves the charge to zero (including a real threshold met by the item price).

## Returns

Returns are disabled by default because food/perishable products cannot safely inherit a single blanket assumption. Admin can enable factual return data and exclude specific product IDs, category IDs or vendor IDs. Filters can implement more specific product-level logic.

When `accepts_returns=false`, no return window should be supplied. This is also validated operationally before onboarding.

## Search eligibility and exclusions

A current offer is eligible only when its parent is published, not password-protected, product visibility is not hidden, the Woo product is purchasable, its vendor is active/approved, and it is not excluded by product/category/vendor configuration or filter.

The following filters allow future project-specific logic without copying the feed generator:

- `mdo_openai_item_id`
- `mdo_openai_title`
- `mdo_openai_description`
- `mdo_openai_brand`
- `mdo_openai_seller_name`
- `mdo_openai_marketplace_seller`
- `mdo_openai_seller_url`
- `mdo_openai_product_category`
- `mdo_openai_availability`
- `mdo_openai_availability_date`
- `mdo_openai_price`
- `mdo_openai_shipping`
- `mdo_openai_returns`
- `mdo_openai_is_eligible_search`
- `mdo_openai_product_excluded`
- `mdo_openai_native_record`
- `mdo_openai_google_compatible_record`
- `mdo_openai_feed_provider`

## Google-compatible alternative

This is an alternative provider, not a renamed copy of the Google Shopping XML feed. It reuses normalization helpers where appropriate but generates a dedicated OpenAI compatibility file.

It is disabled until `google_compatible_confirmed=1`. Required columns are the current compatibility fields `id`, `title`, `description`, `link`, `image_link`, `availability`, `price`, `brand`. Output is UTF-8 CSV.gz or TSV.gz. XML/RSS is deliberately not generated.

In this compatibility mode, OpenAI's **registered merchant display name** becomes the seller identity. Therefore the marketplace commercial structure must be agreed with OpenAI before choosing this path; uploading a `seller_name` does not override the registered merchant identity.

## Delivery

### SFTP

The File Upload documentation describes pushing full snapshots to the SFTP destination assigned during onboarding. EMDO accepts only administrator-supplied values for host, port, username, password/private key, passphrase, remote directory and optional remote filename.

No credential is included in source control. Secrets stored in WordPress options are encrypted with AES-256-GCM using WordPress authentication material as key material. Logs redact secret-looking fields.

Upload is atomic where supported: upload to `.tmp`, then rename over the stable remote snapshot.

### Commerce API abstraction

The API transport has configuration placeholders and the documented authentication/header concepts, but intentionally implements **no guessed product-feed route**. `test_connection()` and `upload()` fail closed unless a project filter implements the exact account contract supplied by OpenAI.

Extension hooks:

- `mdo_openai_api_test_connection`
- `mdo_openai_api_upload`

Do not point this at the general OpenAI model API.

## Admin workflow

Open WordPress admin -> EMDO/El Mercado de Origen (or WooCommerce fallback) -> `Feeds · OpenAI / ChatGPT`.

Safe pre-onboarding workflow:

1. Keep Merchant status as `No solicitado` or the truthful current state.
2. Keep all OpenAI confirmation checkboxes off.
3. Keep delivery `Ninguno` and native `JSONL.gz` selected.
4. Run `Validar catálogo`.
5. Run `Vista previa (20)` and inspect brand, seller, price, stock, variation grouping and URLs.
6. Run `Generar snapshot` to create the full internal QA file.
7. Do **not** send the QA URL to OpenAI as though it were automatically consumed. It is an internal/download endpoint only.

After OpenAI onboarding:

1. Record only capabilities actually confirmed by OpenAI.
2. Enter only the SFTP/API values OpenAI supplies.
3. Test the connection.
4. Perform one manual upload.
5. Review OpenAI's processing/upload report with particular attention to rejected rows, seller identity, market/currency and variant grouping.
6. Enable automatic upload only after the manual upload is accepted.
7. Keep the default six-hour snapshot cadence unless OpenAI asks for another cadence.

## QA

Static regression suite:

`php tests/openai-commerce-contract.php`

GitHub Actions workflow:

`.github/workflows/openai-commerce-feed-ci.yml`

Production-safe runtime QA from the WordPress root:

`wp eval-file /path/to/repo/tools/openai-commerce-runtime-qa.php`

The runtime QA validates the live catalog and prints up to 20 real mapped rows. It also scans live scenario coverage (simple/variable/no-SKU/out-of-stock/sale/reviews/4+ variants/variation out of stock/duplicate SKU), exercises synthetic validator cases, tests UTF-8 and price formatting, validates GTIN behavior and finds real `Los Pedroches` products so their mapped brand/seller/variant data can be reviewed.

The runtime script does not edit products, prices or vendors. Mapping may persist `_mdo_openai_item_id` and `_mdo_openai_group_id`, which is the intended stable-identity behavior.

## Los Pedroches reference case

The public catalog currently includes `Jamón de bellota 100% Ibérico con DOP Los Pedroches (brida negra)` sold by Hidalgo de la Jara. The runtime QA does not hard-code those words as a substitute for catalog data; it finds the real WooCommerce product and reports the mapper's actual `brand`, `seller_name`, variant options, current price and availability. Any mismatch between explicit brand metadata and WCFM vendor data becomes a warning for review rather than an invented correction.

## Deployment and rollback

The integration is additive to the existing `MDO Merchant Feeds (Google + OpenAI Commerce)` loader. Google feed code is not replaced.

Recommended release procedure:

1. Merge only after `OpenAI Commerce Feed CI` succeeds.
2. Deploy the changed MU-plugin files with the same production mechanism used for the rest of `mu-plugins`.
3. Load WP Admin and verify the new `Feeds · OpenAI / ChatGPT` screen without changing delivery settings.
4. Run validation and preview against production data.
5. Generate the snapshot and run the WP-CLI runtime QA.
6. Confirm existing Google Merchant feed URLs and `chatgpt-sitemap.xml` still respond normally.
7. Keep delivery disabled until OpenAI onboarding is approved.

Rollback is file-level: restore the prior `mdo-google-merchant-feeds-20260825.php` loader and remove the `mdo-google-merchant/openai/` directory. The existing Google Merchant and organic ChatGPT discovery modules then continue independently. Generated files/options can remain harmlessly or be deleted after rollback; they are not loaded without the OpenAI module.

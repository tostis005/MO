# EMDO — OpenAI / ChatGPT Product Discovery feed

Last reviewed against the official OpenAI Agentic Commerce documentation: 2026-09-10.

## Scope

This integration is part of the existing WordPress plugin **EMDO** (`mdo-supplier-sync`). It does not create or depend on a separate OpenAI plugin and it does not replace the Google Merchant feed.

Its purpose is Product Discovery in ChatGPT. Checkout remains on `https://www.elmercadodeorigen.com/`. Instant Checkout is out of scope.

The existing `mdo-chatgpt-discovery.php` MU-plugin remains separate: it controls organic crawling/discovery (`OAI-SearchBot`, `ChatGPT-User` and the ChatGPT sitemap). A direct merchant Product Feed is a different onboarding and ingestion mechanism.

## Specification change detected

**CAMBIO DE ESPECIFICACIÓN DETECTADO:** the current standard OpenAI File Upload documentation says standard OpenAI-format uploads target the **US** by default. Row-level country/currency data does not enable another market. Additional markets/currencies must be enabled only after OpenAI confirms them for the merchant integration.

EMDO can therefore generate and validate the real Spain/EUR catalog before onboarding, but delivery is blocked until both conditions are explicitly confirmed in admin:

- direct Product Feed access granted by OpenAI;
- Spain/EUR confirmed for this integration.

Google-compatible mode is also blocked until OpenAI explicitly registers/confirms that path for the merchant account.

## Official sources

- Products: https://developers.openai.com/commerce/specs/file-upload/products
- File Upload overview: https://developers.openai.com/commerce/specs/file-upload/overview
- Commerce API overview: https://developers.openai.com/commerce/specs/api/overview
- Powering Product Discovery in ChatGPT: https://openai.com/index/powering-product-discovery-in-chatgpt/
- Shopping with ChatGPT Search: https://help.openai.com/en/articles/11128490-improved-shopping-results-from-chatgpt-search
- Merchant Feed Terms: https://openai.com/policies/merchant-feed-terms-of-service/

Re-check the official documentation before enabling a new market, transport, optional field family or checkout capability.

## EMDO architecture

Plugin bootstrap:

`mdo-supplier-sync/mdo-supplier-sync.php`

OpenAI module:

- `mdo-supplier-sync/includes/openai/class-mdo-openai-commerce.php`: WooCommerce/WCFM mapping, validation, providers, full-snapshot generation, preview, schedules and 14-day delisting manifest.
- `mdo-supplier-sync/includes/openai/class-mdo-openai-transports.php`: encrypted secrets, SFTP transport and a deliberately fail-closed Commerce API abstraction.
- `mdo-supplier-sync/includes/openai/class-mdo-openai-admin.php`: configuration, onboarding status, validation, preview, generation, connection test and manual upload.

The Google Merchant MU-plugin remains independent and is not an OpenAI loader. OpenAI can reuse existing factual normalization helpers when they are available, but generation and delivery live in EMDO and do not require a Google-format feed.

Generation and delivery are separate operations. A catalog can be generated and QA'd without OpenAI credentials.

## Native full snapshot

Primary format: UTF-8 `JSONL.gz`, one JSON object per line.

Stable filename:

`mercado-de-origen-products.jsonl.gz`

Optional native outputs:

- `mercado-de-origen-products.csv.gz`
- `mercado-de-origen-products.tsv.gz`

The generator streams directly to gzip in batches and atomically replaces the local snapshot.

Default schedule: every 6 hours. Available schedules: manual, hourly, 6 hours, 12 hours and daily.

## Required native fields

The validator requires the current nine Product Discovery fields:

- `item_id`
- `title`
- `description`
- `url`
- `brand`
- `seller_name`
- `image_url`
- `availability`
- `price`

Product and image URLs must be public absolute HTTPS URLs.

## WooCommerce product/variation model

A simple purchasable product produces one offer.

A variable product does not produce a single parent offer. Every published purchasable variation produces its own row with its own current price, stock, image/URL when available and variation options.

Stable identity order:

1. persisted `_mdo_openai_item_id`;
2. SKU only when unique in WooCommerce;
3. simple fallback `wc-{product_id}`;
4. variation fallback `wc-{parent_id}-v{variation_id}`.

The chosen ID is persisted so later SKU edits do not silently rewrite identity.

Variable products use separate persisted `group_id` values (`wcg-{parent_id}`), `listing_has_variations=true` and a factual `variant_dict`. A `group_id` must never equal an offer `item_id`.

`offer_id` is stable and based on seller identity + item identity; price is never part of it.

## Seller, producer/brand and marketplace

The store operates as a marketplace/intermediary in the production legal/commercial structure. The default feed model is therefore:

- `seller_name`: actual WCFM seller/vendor attached to the product;
- `brand`: actual producer/brand from WooCommerce taxonomy/meta, with the WCFM store identity only as a factual final fallback;
- `marketplace_seller`: `El Mercado de Origen` only when OpenAI has explicitly enabled/configured that field for the feed;
- `seller_url`: WCFM store URL when available.

The admin also supports `El Mercado de Origen` as seller and a custom-filter seller model for future commercial structures.

No seller or brand is inferred from product-title words.

Central resolver: `mdo_get_openai_brand( WC_Product $product )`.

A brand/vendor mismatch is a QA warning, not an automatic rewrite, because producer and contractual seller can legitimately differ.

## Food descriptions and attributes

HTML and shortcodes are stripped and whitespace is normalized.

Descriptions are enriched only with data that already exists in WooCommerce. Recognized concepts include DOP/denomination, breed, feeding, seal, origin, producer, format and curing.

Unsupported business attributes are not invented as arbitrary OpenAI columns; they remain in description/category/variant data when appropriate.

## Availability

Native mapping:

- Woo `instock` -> `in_stock`
- Woo `outofstock` -> `out_of_stock`
- Woo `onbackorder` -> `backorder`
- explicit `_mdo_openai_preorder=yes` -> `pre_order`
- otherwise -> `unknown`

A future availability date is never fabricated. A real value can be supplied through `_mdo_openai_availability_date` or the `mdo_openai_availability_date` filter.

## Price and sale price

WooCommerce display prices are emitted as `amount CURRENCY`, for example `440.00 EUR`.

When WooCommerce has a genuine active sale, regular price is `price` and the lower current sale value is `sale_price`. Validation requires the same currency and `0 < sale_price < price`.

No payable price is invented.

## Images

Primary image order:

1. variation image;
2. parent image.

Additional gallery images are emitted as `additional_image_urls` in native JSONL and converted to the appropriate delimited representation when needed.

## Product category

`product_category` represents the real WooCommerce hierarchy (`Parent > Child`) through the OpenAI mapping filter/common catalog normalizer. Missing category data is treated as a quality gap rather than fabricated.

## Weight

`weight` + `item_weight_unit` are emitted only for a real exact positive WooCommerce weight in `g`, `kg`, `oz` or `lb`.

A variation label such as `7–8 kg` remains variation data and is not converted to an invented exact weight.

## GTIN and MPN

A GTIN is emitted only when a real identifier passes the existing catalog checksum validation for 8/12/13/14 digits. Leading zeros are preserved. MPN is emitted only when a real stored identifier exists.

No identifier is manufactured.

## Reviews

Only WooCommerce product-review aggregates are used. Seller/store reviews are not mixed into product `review_count`/`star_rating`; both fields must describe the same review population.

## Shipping

Shipping is disabled by default.

Even if an admin selects a representation, shipping data is not emitted until `shipping_capability_confirmed=1` after onboarding. The implementation can reuse the factual EMDO/WCFM shipping calculation already used by the commerce catalog.

Supported representations are mutually exclusive:

- `shipping_price`; or
- OpenAI shipping tuple.

Zero is emitted only when the real shipping calculation resolves to free shipping for that offer/condition.

## Returns

Returns are disabled by default because food/perishable products must not inherit an invented blanket policy.

Admin can enable factual return data and exclude product IDs, category IDs and vendor IDs. Product-specific rules can be implemented through `mdo_openai_returns`.

Current OpenAI field names used by EMDO are `accepts_returns`, `return_deadline_in_days` and `return_policy`.

## Search eligibility, exclusions and deletion retention

A current offer is exported only when it is published, passwordless, not hidden, purchasable and not excluded by product/category/vendor configuration or filters. Existing vendor-activity normalization is reused when available.

Native format supports `is_eligible_search=false` for explicit delisting.

EMDO keeps a lightweight manifest of previously exported native item IDs. An offer that disappears from the current eligible catalog can remain in snapshots for up to 14 days with `is_eligible_search=false`, matching current OpenAI retention guidance, and is then removed.

## Google-compatible alternative

This is a dedicated alternative provider, not the Google Merchant XML feed copied or renamed.

It is disabled until `google_compatible_confirmed=1`.

Supported outputs are UTF-8 CSV.gz/TSV.gz; XML/RSS/Atom are intentionally not produced.

Current required compatibility columns are:

`id`, `title`, `description`, `link`, `image_link`, `availability`, `price`, `brand`.

Native `pre_order` maps to Google-compatible `preorder`. Google-compatible `preorder` and `backorder` require a real `availability_date`; the row is rejected if it is missing.

If neither a valid GTIN nor a real MPN exists, `identifier_exists=no` is emitted.

In this compatibility path the registered OpenAI merchant identity determines seller identity; an uploaded `seller_name` cannot override it. Marketplace semantics must therefore be agreed during onboarding before selecting this provider.

## SFTP delivery

OpenAI File Upload uses the SFTP destination supplied during onboarding. Admin accepts only operator-provided values for host, port, username, password/private key, passphrase, remote directory and optional remote filename.

No SFTP credential is hard-coded or committed.

Stored secrets are encrypted with AES-256-GCM using WordPress authentication material as key material. Logs redact secret-like context fields.

Upload uses a temporary remote filename followed by rename/replacement where the server supports it.

## Commerce API abstraction

The code has a separate API transport because OpenAI publishes a Commerce Feed API specification. It intentionally does not guess a base URL/feed path for this merchant account.

Until exact onboarding values/contract are supplied, API `test_connection()` and `upload()` fail closed. Account-specific implementation can be supplied through:

- `mdo_openai_api_test_connection`
- `mdo_openai_api_upload`

Do not point this transport at the general OpenAI model API.

## Admin screen

WordPress admin -> WooCommerce/EMDO -> `Feeds · OpenAI / ChatGPT`.

The page displays:

- merchant onboarding status;
- direct-feed access confirmation;
- Spain/EUR confirmation;
- current exportable offer count;
- format and schedule;
- seller/marketplace settings;
- exclusions;
- shipping/returns capabilities;
- SFTP/API configuration;
- validation report;
- 20-row preview;
- generation/upload actions;
- redacted recent logs.

The downloadable QA URL is explicitly labelled as an internal/operational download. OpenAI does not automatically consume that URL.

## Safe pre-onboarding workflow

1. Keep merchant status truthful (`No solicitado` until application is sent).
2. Keep direct feed, Spain/EUR and Google-compatible confirmations disabled.
3. Keep delivery `Ninguno`.
4. Keep native `JSONL.gz`.
5. Run `Validar catálogo`.
6. Review `Vista previa (20)` for producer/brand, seller, variation grouping, current price, availability and HTTPS URLs.
7. Generate a full local snapshot for QA.
8. Do not send the QA download URL as if it were an ingestion endpoint.

## Workflow after OpenAI approval

1. Record only capabilities OpenAI explicitly confirms.
2. Enter only the SFTP/API values supplied by OpenAI.
3. Test the configured connection.
4. Generate and upload one full snapshot manually.
5. Review OpenAI processing/rejection results, especially seller identity, market/currency and variation grouping.
6. Enable automatic upload only after the manual feed is accepted.
7. Keep the six-hour cadence unless OpenAI requests another cadence.

## QA and tests

Static regression suite:

`php tests/openai-commerce-contract.php`

CI:

`.github/workflows/openai-commerce-feed-ci.yml`

Production-safe WP-CLI QA:

`wp eval-file /path/to/repo/tools/openai-commerce-runtime-qa.php`

The runtime QA validates the real catalog, samples up to 20 mapped offers, covers difficult catalog scenarios, and searches real Los Pedroches products so their actual producer/brand, WCFM seller, variations, price and availability can be reviewed without hard-coding replacement values.

It does not edit products, vendors or prices. The mapper can persist `_mdo_openai_item_id` and `_mdo_openai_group_id`, which is intentional stable-identity behavior.

## Deployment and rollback

Release only after `OpenAI Commerce Feed CI` passes.

Deploy the updated `mdo-supplier-sync` plugin through the existing production deployment mechanism. The Google Merchant MU-plugin does not need to be replaced for OpenAI activation.

After deployment, open the admin screen with delivery still disabled, run validation/preview and generate a local QA snapshot.

Rollback is code-only: revert the OpenAI Commerce feature commit(s) or restore the previous `mdo-supplier-sync` version. Generated files live under WordPress uploads and contain no credentials; stored delivery secrets remain in WordPress options and should be cleared from admin if onboarding is abandoned.

## Extension hooks

Core mapping/customization hooks include:

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

The transport-specific API hooks are listed above.
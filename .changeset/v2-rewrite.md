---
"wp-content-connect": major
---

2.0.0 is a major rewrite of the editor UI and REST surface. **Stored relationship data and the database schema are unchanged**, and the documented public API is preserved: the relationship objects and their data methods (`add_relationship()`, `delete_relationship()`, `replace_relationships()`, the `save_*_sort_data()` family, etc.), `WP_Query` relationship queries, the helper functions, and the `content-connect/v1/search` endpoint URL all continue to work. No action is required if you use these documented interfaces.

**Breaking Changes**

The changes below affect only code that referenced plugin **internals** that were never part of the documented API:

- **Moved class:** `TenUp\ContentConnect\API\Search` is now `TenUp\ContentConnect\API\V1\Search`. The `content-connect/v1/search` REST endpoint URL is unchanged.
- **Removed UI classes:** `UI\MetaBox`, `UI\PostToPost`, `UI\PostToUser`, and `UI\PostUI` were replaced by the React-based `UI\BlockEditor` and `UI\ClassicEditor`.
- **`Plugin` members:** the `$url`, `$version`, `$meta_box`, `$search`, `$deleted_items`, `$wp_query_integration`, and `$user_query_integration` public properties were removed — use the `CONTENT_CONNECT_URL`, `CONTENT_CONNECT_VERSION`, and `CONTENT_CONNECT_PATH` constants instead. `Plugin::wp_init()` was renamed to `Plugin::init()`.
- **Relationship internals:** `Relationship::setup()`, `PostToPost::setup()`, `PostToUser::setup()`, and the `$from_ui` / `$to_ui` properties were removed. `Registry::define_post_to_post()` and `define_post_to_user()` no longer instantiate UI objects.
- **Editor assets:** the `tenup-content-connect` script handle and the `window.ContentConnectData` global were removed. The Classic Editor metabox markup and save flow changed — relationships now persist through the REST v2 API instead of a `save_post` form submission.
- **Removed filter:** `tenup_content_connect_localize_data`, which only filtered the removed Vue interface's localized payload, no longer fires. The `tenup_content_connect_post_relationship_data` filter is retained (now applied in the data layer, with added `$rel_type`, `$other_post_type`, and `$context` arguments).

**Added**

- Helper functions for retrieving relationship data: `get_post_to_post_relationships_by()`, `get_post_to_user_relationships_by()`, `get_post_relationships_data()`, `get_post_to_post_relationships_data()`, and `get_post_to_user_relationships_data()` (props [@s3rgiosan](https://github.com/s3rgiosan) via [#96](https://github.com/10up/wp-content-connect/pull/96)).
- REST API v2 endpoint `GET /content-connect/v2/relationships` to retrieve all relationships by type, with optional filtering by key, post type, or from/to post types (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- REST API v2 endpoint `GET /content-connect/v2/post/{post_id}/relationships` to retrieve all relationships for a post, with filtering by relationship type, post type, and context (`view`/`embed`) (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- REST API v2 endpoints `GET|POST|PUT|DELETE /content-connect/v2/post/{post_id}/related` to retrieve (with pagination and ordering), replace, add, and remove related entities for a post, preserving sort order for sortable relationships (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- Automatic `content-connect:relationships` and `content-connect:related` REST API links on post responses for post types that support REST (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- WordPress Data API store (`wp-content-connect`) for managing Content Connect relationships within the Block Editor (props [@fabiankaegy](https://github.com/fabiankaegy), [@barryceelen](https://github.com/barryceelen), [@s3rgiosan](https://github.com/s3rgiosan) via [#94](https://github.com/10up/wp-content-connect/pull/94)).
- React hooks `useRelationships()` and `useRelatedEntities()` for accessing and managing relationships in custom blocks (props [@fabiankaegy](https://github.com/fabiankaegy), [@barryceelen](https://github.com/barryceelen), [@s3rgiosan](https://github.com/s3rgiosan) via [#94](https://github.com/10up/wp-content-connect/pull/94)).
- Block Editor UI integration with automatic relationship panels in the Document Settings sidebar (props [@fabiankaegy](https://github.com/fabiankaegy), [@barryceelen](https://github.com/barryceelen), [@s3rgiosan](https://github.com/s3rgiosan) via [#94](https://github.com/10up/wp-content-connect/pull/94)).
- WordPress JavaScript filter hooks `contentConnect.searchResultFilter`, `contentConnect.pickedItemFilter`, and `contentConnect.pickedItemPreviewComponent` for customizing the Block Editor UI, with relationship context awareness (props [@s3rgiosan](https://github.com/s3rgiosan) via [#104](https://github.com/10up/wp-content-connect/pull/104)).
- WordPress 7.0 compatibility; set WordPress minimum supported version to 6.8 (props [@mphillips](https://github.com/mphillips) via [#120](https://github.com/10up/wp-content-connect/pull/120)).
- WordPress Playground blueprint (`.github/blueprints/blueprint.json`) for a one-click, zero-install plugin demo (props [@s3rgiosan](https://github.com/s3rgiosan) via [#122](https://github.com/10up/wp-content-connect/pull/122)).
- `tenup-content-connect-update-relationships-order` action for developers to run logic after relationship order is saved, firing for post-to-post, post-to-user, and user-to-post relationships (props [@ocean90](https://github.com/ocean90) via [#123](https://github.com/10up/wp-content-connect/pull/123)).
- Query Loop block integration that filters posts by Content Connect relationships, in the editor preview and on the front end (props [@fabiankaegy](https://github.com/fabiankaegy), [@s3rgiosan](https://github.com/s3rgiosan) via [#98](https://github.com/10up/wp-content-connect/pull/98)).
- PHPStan static analysis and a PHPCompatibility check for PHP 8.2–8.5, run in CI (props [@s3rgiosan](https://github.com/s3rgiosan) via [#135](https://github.com/10up/wp-content-connect/pull/135)).

**Changed**

- Replaced the Vue-based Classic Editor UI with a React-based implementation that shares the Block Editor's components and `@wordpress/data` store, for a consistent experience across both editors (props [@s3rgiosan](https://github.com/s3rgiosan) via [#97](https://github.com/10up/wp-content-connect/pull/97)).
- Bump WordPress "tested up to" version to 7.1 (props [@zamanq](https://github.com/zamanq) via [#133](https://github.com/10up/wp-content-connect/pull/133)).
- Bump minimum PHP version to 8.2 and test through PHP 8.5 (props [@s3rgiosan](https://github.com/s3rgiosan) via [#135](https://github.com/10up/wp-content-connect/pull/135)).
- Update the PHPCS toolchain so the 10up ruleset and PHPCompatibilityWP sniffs install correctly (props [@s3rgiosan](https://github.com/s3rgiosan) via [#135](https://github.com/10up/wp-content-connect/pull/135)).

**Deprecated**

- The REST API v1 search endpoint (`POST /content-connect/v1/search`). Use the `content-connect/v2` endpoints instead (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).

**Removed**

- Vue.js dependency and the `vue-resource` package (props [@s3rgiosan](https://github.com/s3rgiosan) via [#97](https://github.com/10up/wp-content-connect/pull/97)).

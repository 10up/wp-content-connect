---
"wp-content-connect": major
---

pr: 94
author: @fabiankaegy
author: @barryceelen
author: @s3rgiosan

2.0.0 is a major rewrite of the editor UI and REST surface. **Stored relationship data and the database schema are unchanged**, and the documented public API is preserved: the relationship objects and their data methods (`add_relationship()`, `delete_relationship()`, `replace_relationships()`, the `save_*_sort_data()` family, etc.), `WP_Query` relationship queries, the helper functions, and the `content-connect/v1/search` endpoint URL all continue to work. No action is required if you use these documented interfaces.

The changes below affect only code that referenced plugin **internals** that were never part of the documented API:

- **Moved class:** `TenUp\ContentConnect\API\Search` is now `TenUp\ContentConnect\API\V1\Search`. The `content-connect/v1/search` REST endpoint URL is unchanged.
- **Removed UI classes:** `UI\MetaBox`, `UI\PostToPost`, `UI\PostToUser`, and `UI\PostUI` were replaced by the React-based `UI\BlockEditor` and `UI\ClassicEditor`.
- **`Plugin` members:** the `$url`, `$version`, `$meta_box`, `$search`, `$deleted_items`, `$wp_query_integration`, and `$user_query_integration` public properties were removed — use the `CONTENT_CONNECT_URL`, `CONTENT_CONNECT_VERSION`, and `CONTENT_CONNECT_PATH` constants instead. `Plugin::wp_init()` was renamed to `Plugin::init()`.
- **Relationship internals:** `Relationship::setup()`, `PostToPost::setup()`, `PostToUser::setup()`, and the `$from_ui` / `$to_ui` properties were removed. `Registry::define_post_to_post()` and `define_post_to_user()` no longer instantiate UI objects.
- **Editor assets:** the `tenup-content-connect` script handle and the `window.ContentConnectData` global were removed. The Classic Editor metabox markup and save flow changed — relationships now persist through the REST v2 API instead of a `save_post` form submission.
- **Removed filter:** `tenup_content_connect_localize_data`, which only filtered the removed Vue interface's localized payload, no longer fires. The `tenup_content_connect_post_relationship_data` filter is retained (now applied in the data layer, with added `$rel_type`, `$other_post_type`, and `$context` arguments).

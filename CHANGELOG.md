# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [2.0.0] - TBD

### Breaking Changes

2.0.0 is a major rewrite of the editor UI and REST surface. **Stored relationship data and the database schema are unchanged**, and the documented public API is preserved: the relationship objects and their data methods (`add_relationship()`, `delete_relationship()`, `replace_relationships()`, the `save_*_sort_data()` family, etc.), `WP_Query` relationship queries, the helper functions, and the `content-connect/v1/search` endpoint URL all continue to work. No action is required if you use these documented interfaces.

The changes below affect only code that referenced plugin **internals** that were never part of the documented API:

- **Moved class:** `TenUp\ContentConnect\API\Search` is now `TenUp\ContentConnect\API\V1\Search`. The `content-connect/v1/search` REST endpoint URL is unchanged.
- **Removed UI classes:** `UI\MetaBox`, `UI\PostToPost`, `UI\PostToUser`, and `UI\PostUI` were replaced by the React-based `UI\BlockEditor` and `UI\ClassicEditor`.
- **`Plugin` members:** the `$url`, `$version`, `$meta_box`, `$search`, `$deleted_items`, `$wp_query_integration`, and `$user_query_integration` public properties were removed — use the `CONTENT_CONNECT_URL`, `CONTENT_CONNECT_VERSION`, and `CONTENT_CONNECT_PATH` constants instead. `Plugin::wp_init()` was renamed to `Plugin::init()`.
- **Relationship internals:** `Relationship::setup()`, `PostToPost::setup()`, `PostToUser::setup()`, and the `$from_ui` / `$to_ui` properties were removed. `Registry::define_post_to_post()` and `define_post_to_user()` no longer instantiate UI objects.
- **Editor assets:** the `tenup-content-connect` script handle and the `window.ContentConnectData` global were removed. The Classic Editor metabox markup and save flow changed — relationships now persist through the REST v2 API instead of a `save_post` form submission.
- **Removed filter:** `tenup_content_connect_localize_data`, which only filtered the removed Vue interface's localized payload, no longer fires. The `tenup_content_connect_post_relationship_data` filter is retained (now applied in the data layer, with added `$rel_type`, `$other_post_type`, and `$context` arguments).

### Added

- Helper functions for retrieving relationship data: `get_post_to_post_relationships_by()`, `get_post_to_user_relationships_by()`, `get_post_relationships_data()`, `get_post_to_post_relationships_data()`, and `get_post_to_user_relationships_data()` (props [@s3rgiosan](https://github.com/s3rgiosan) via [#96](https://github.com/10up/wp-content-connect/pull/96)).
- REST API v2 endpoint `GET /content-connect/v2/relationships` to retrieve all relationships by type, with optional filtering by key, post type, or from/to post types (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- REST API v2 endpoint `GET /content-connect/v2/post/{post_id}/relationships` to retrieve all relationships for a post, with filtering by relationship type, post type, and context (`view`/`embed`) (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- REST API v2 endpoints `GET|POST|PUT|DELETE /content-connect/v2/post/{post_id}/related` to retrieve (with pagination and ordering), replace, add, and remove related entities for a post, preserving sort order for sortable relationships (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- Automatic `content-connect:relationships` and `content-connect:related` REST API links on post responses for post types that support REST (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).
- WordPress Data API store (`wp-content-connect`) for managing Content Connect relationships within the Block Editor (props [@fabiankaegy](https://github.com/fabiankaegy), [@s3rgiosan](https://github.com/s3rgiosan) via [#94](https://github.com/10up/wp-content-connect/pull/94)).
- React hooks `useRelationships()` and `useRelatedEntities()` for accessing and managing relationships in custom blocks (props [@fabiankaegy](https://github.com/fabiankaegy), [@s3rgiosan](https://github.com/s3rgiosan) via [#94](https://github.com/10up/wp-content-connect/pull/94)).
- Block Editor UI integration with automatic relationship panels in the Document Settings sidebar (props [@fabiankaegy](https://github.com/fabiankaegy), [@s3rgiosan](https://github.com/s3rgiosan) via [#94](https://github.com/10up/wp-content-connect/pull/94)).
- WordPress JavaScript filter hooks `contentConnect.searchResultFilter`, `contentConnect.pickedItemFilter`, and `contentConnect.pickedItemPreviewComponent` for customizing the Block Editor UI, with relationship context awareness (props [@s3rgiosan](https://github.com/s3rgiosan) via [#104](https://github.com/10up/wp-content-connect/pull/104)).
- WordPress 7.0 compatibility; set WordPress minimum supported version to 6.8 (props [@s3rgiosan](https://github.com/s3rgiosan) via [#120](https://github.com/10up/wp-content-connect/pull/120)).

### Changed

- Replaced the Vue-based Classic Editor UI with a React-based implementation that shares the Block Editor's components and `@wordpress/data` store, for a consistent experience across both editors (props [@s3rgiosan](https://github.com/s3rgiosan), [@fabiankaegy](https://github.com/fabiankaegy) via [#97](https://github.com/10up/wp-content-connect/pull/97)).

### Deprecated

- The REST API v1 search endpoint (`POST /content-connect/v1/search`). Use the `content-connect/v2` endpoints instead (props [@s3rgiosan](https://github.com/s3rgiosan) via [#95](https://github.com/10up/wp-content-connect/pull/95)).

### Removed

- Vue.js dependency and the `vue-resource` package (props [@s3rgiosan](https://github.com/s3rgiosan), [@fabiankaegy](https://github.com/fabiankaegy) via [#97](https://github.com/10up/wp-content-connect/pull/97)).

## [1.6.0] - 2025-02-14

### Added

- Filters for the Post UI `WP_Query` and `WP_User_Query` args (props [@s3rgiosan](https://github.com/s3rgiosan), [@rickalee](https://github.com/rickalee) via [#67](https://github.com/10up/wp-content-connect/pull/67)).
- Apply `tenup_content_connect_final_post` and `tenup_content_connect_final_user` filters to search results (props [@s3rgiosan](https://github.com/s3rgiosan), [@webdados](https://github.com/webdados) via [#86](https://github.com/10up/wp-content-connect/pull/86)).

### Changed

- Bump WordPress "tested up to" version to 6.7 (props [@s3rgiosan](https://github.com/s3rgiosan), [@jeffpaul](https://github.com/jeffpaul), [@benlk](https://github.com/benlk), [@webdados](https://github.com/webdados) via [#80](https://github.com/10up/wp-content-connect/pull/80)).
- Set WordPress minimum supported version to 6.5 (props [@s3rgiosan](https://github.com/s3rgiosan), [@jeffpaul](https://github.com/jeffpaul), [@benlk](https://github.com/benlk), [@webdados](https://github.com/webdados) via [#80](https://github.com/10up/wp-content-connect/pull/80)).
- Set PHP minimum supported version to 7.4 (props [@s3rgiosan](https://github.com/s3rgiosan), [@jeffpaul](https://github.com/jeffpaul), [@benlk](https://github.com/benlk), [@webdados](https://github.com/webdados) via [#80](https://github.com/10up/wp-content-connect/pull/80)).
- Documentation updates (props [@jeffpaul](https://github.com/jeffpaul), [@moraleida](https://github.com/moraleida), [@dinhtungdu](https://github.com/dinhtungdu) via [#38](https://github.com/10up/wp-content-connect/pull/38), [#39](https://github.com/10up/wp-content-connect/pull/39), [#47](https://github.com/10up/wp-content-connect/pull/47), [#60](https://github.com/10up/wp-content-connect/pull/60)).

### Removed

- Remove `ip` package dependency (props [@dependabot](https://github.com/apps/dependabot) via [#90](https://github.com/10up/wp-content-connect/pull/90)).

### Fixed

- Item deletion in search (props [@kirtangajjar](https://github.com/kirtangajjar), [@s3rgiosan](https://github.com/s3rgiosan) via [#70](https://github.com/10up/wp-content-connect/pull/70)).
- Check if `$post` is instance of `\WP_Post` before adding meta boxes (props [@s3rgiosan](https://github.com/s3rgiosan), [@webdados](https://github.com/webdados), [(@rickalee)](https://github.com/rickalee) via [#77](https://github.com/10up/wp-content-connect/pull/77)).

### Security

- Bump `lodash.mergewith` from 4.6.1 to 4.6.2 (props [@dependabot](https://github.com/apps/dependabot) via [#40](https://github.com/10up/wp-content-connect/pull/40)).
- Bump `lodash` from 4.17.11 to 4.17.21 (props [@dependabot](https://github.com/apps/dependabot) via [#41](https://github.com/10up/wp-content-connect/pull/41), [#52](https://github.com/10up/wp-content-connect/pull/52), [#56](https://github.com/10up/wp-content-connect/pull/56)).
- Bump `tar` from 2.2.1 to 4.4.19 (props [@dependabot](https://github.com/apps/dependabot) via [#42](https://github.com/10up/wp-content-connect/pull/42), [#64](https://github.com/10up/wp-content-connect/pull/64)).
- Bump `fstream` from 1.0.11 to 1.0.12 (props [@dependabot](https://github.com/apps/dependabot) via [#43](https://github.com/10up/wp-content-connect/pull/43)).
- Bump `mixin-deep` from 1.3.1 to 1.3.2 (props [@dependabot](https://github.com/apps/dependabot) via [#44](https://github.com/10up/wp-content-connect/pull/44)).
- Bump `acorn` from 6.1.1 to 6.4.1 (props [@dependabot](https://github.com/apps/dependabot) via [#48](https://github.com/10up/wp-content-connect/pull/48)).
- Bump `elliptic` from 6.4.1 to 6.6.1 (props [@dependabot](https://github.com/apps/dependabot) via [#51](https://github.com/10up/wp-content-connect/pull/51), [#54](https://github.com/10up/wp-content-connect/pull/54), [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `node-sass` from 4.11.0 to 9.0.0 (props [@dependabot](https://github.com/apps/dependabot) via [#53](https://github.com/10up/wp-content-connect/pull/53), [#63](https://github.com/10up/wp-content-connect/pull/63), [#73](https://github.com/10up/wp-content-connect/pull/73), [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `y18n` from 3.2.1 to 3.2.2 (props [@dependabot](https://github.com/apps/dependabot) via [#55](https://github.com/10up/wp-content-connect/pull/55)).
- Bump `hosted-git-info` from 2.8.8 to 2.8.9 (props [@dependabot](https://github.com/apps/dependabot) via [#57](https://github.com/10up/wp-content-connect/pull/57)).
- Bump `path-parse` from 1.0.6 to 1.0.7 (props [@dependabot](https://github.com/apps/dependabot) via [#58](https://github.com/10up/wp-content-connect/pull/58)).
- Bump `cached-path-relative` from 1.0.2 to 1.1.0 (props [@dependabot](https://github.com/apps/dependabot) via [#61](https://github.com/10up/wp-content-connect/pull/61)).
- Bump `ini` from 1.3.5 to 1.3.8 (props [@dependabot](https://github.com/apps/dependabot) via [#62](https://github.com/10up/wp-content-connect/pull/62)).
- Bump `decode-uri-component` from 0.2.0 to 0.2.2 (props [@dependabot](https://github.com/apps/dependabot) via [#68](https://github.com/10up/wp-content-connect/pull/68)).
- Bump `minimatch` from 3.0.4 to 3.0.8 (props [@dependabot](https://github.com/apps/dependabot) via [#72](https://github.com/10up/wp-content-connect/pull/72)).
- Bump `scss-tokenizer` from 0.2.3 to 0.4.3 (props [@dependabot](https://github.com/apps/dependabot) via [#73](https://github.com/10up/wp-content-connect/pull/73)).
- Bump `minimist` from 1.2.0 to 1.2.8 (props [@dependabot](https://github.com/apps/dependabot) via [#88](https://github.com/10up/wp-content-connect/pull/88)).
- Bump `mkdirp` from 0.5.1 to 0.5.6 (props [@dependabot](https://github.com/apps/dependabot) via [#88](https://github.com/10up/wp-content-connect/pull/88)).
- Bump `vue-resource` from 1.3.4 to 1.5.3 (props [@dependabot](https://github.com/apps/dependabot) via [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `watchify` from 3.11.1 to 4.0.0 (props [@dependabot](https://github.com/apps/dependabot) via [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `braces` from 2.3.2 to 3.0.3 (props [@dependabot](https://github.com/apps/dependabot) via [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `browserify-sign` from 4.0.4 to 4.2.3 (props [@dependabot](https://github.com/apps/dependabot) via [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `fsevents` from 1.2.7 to 2.3.3 (props [@dependabot](https://github.com/apps/dependabot) via [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `got` from 7.1.0 to 11.8.6 (props [@dependabot](https://github.com/apps/dependabot) via [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `shell-quote` from 1.6.1 to 1.8.2 (props [@dependabot](https://github.com/apps/dependabot) via [#89](https://github.com/10up/wp-content-connect/pull/89)).
- Bump `socks` from 2.7.1 to 2.8.4 (props [@dependabot](https://github.com/apps/dependabot) via [#90](https://github.com/10up/wp-content-connect/pull/90)).

## [1.5.0] - 2019-03-29

- Additional filters on search REST endpoint

## [1.4.0] - 2019-03-25

- Update versions to 1.4.0.

## [1.3.0] - 2019-03-01

- Update npm modules + Update version to 1.3.0 for release.

## [1.2.0] - 2018-06-12

- Changes the WP Query integration to reference `query_vars` instead of `query` on `WP_Query` objects to support alterations to the query via the `pre_get_posts` hook.

## [1.1.0] - 2018-06-12

- Allows the `$to` parameter to accept an array of multiple post types in post to post relationships, so that you can relate a single post type to many post types in the same relationship.

## [1.0.0] - 2018-06-12

- Initial plugin release.

[Unreleased]: https://github.com/10up/wp-content-connect/compare/2.0.0...develop
[2.0.0]: https://github.com/10up/wp-content-connect/compare/1.6.0...2.0.0
[1.6.0]: https://github.com/10up/wp-content-connect/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/10up/wp-content-connect/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/10up/wp-content-connect/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/10up/wp-content-connect/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/10up/wp-content-connect/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/10up/wp-content-connect/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/10up/wp-content-connect/releases/tag/1.0.0

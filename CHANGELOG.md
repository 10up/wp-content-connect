# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

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

[Unreleased]: https://github.com/10up/wp-content-connect/compare/master...develop
[1.6.0]: https://github.com/10up/wp-content-connect/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/10up/wp-content-connect/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/10up/wp-content-connect/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/10up/wp-content-connect/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/10up/wp-content-connect/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/10up/wp-content-connect/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/10up/wp-content-connect/releases/tag/1.0.0

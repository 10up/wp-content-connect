# Changesets

Every pull request that changes how the plugin behaves needs a changeset. Run `npm run changeset`, pick the bump (patch for fixes, minor for features, major for breaking changes), and describe the change for the changelog. Pull requests that don't need one (CI, docs, tests) can use the `no changeset` label.

Releases come from CI (`.github/workflows/release.yml`):

- **`develop`** is the default branch. When pull requests with changesets merge, the release workflow versions them in pre-release mode (`X.Y.Z-beta.N`), commits that as `chore: apply changesets`, and publishes a GitHub prerelease. The changeset files stay, so they carry over to `trunk`.
- **`trunk`** gets a release by merging `develop` into it. The workflow leaves pre-release mode, turns the changesets into the final version and changelog, publishes the release, and merges `trunk` back into `develop`.

Built assets (`dist/`) are never committed to `develop` or `trunk`. For each release the workflow builds the plugin and commits the source plus `dist/` onto the `built` branch, then tags that commit `X.Y.Z[-beta.N]`. Tags are what Composer and `git` installs resolve to, so they always contain a working plugin. Every release also has a zip attached, made with `git archive` from the tag (honoring `.gitattributes` `export-ignore`).

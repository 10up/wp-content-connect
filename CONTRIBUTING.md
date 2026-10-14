# Contributing and Maintaining

First, thank you for taking the time to contribute!

The following is a set of guidelines for contributors as well as information and instructions around our maintenance process. The two are closely tied together in terms of how we all work together and set expectations, so while you may not need to know everything in here to submit an issue or pull request, it's best to keep them in the same document.

## Ways to contribute

Contributing isn't just writing code - it's anything that improves the project. All contributions for this project are managed right here on GitHub. Here are some ways you can help:

### Reporting bugs

If you're running into an issue with the plugin, please take a look through [existing issues](https://github.com/10up/wp-content-connect/issues) and [open a new one](https://github.com/10up/wp-content-connect/issues/new) if needed. If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant.

### Suggesting enhancements

New features and enhancements are also managed via [issues](https://github.com/10up/wp-content-connect/issues). As project owners, 10up sets the direction and roadmap and may not prioritize or decide to implement if outside of the main goals of the plugin.

### Pull requests

Pull requests represent a proposed solution to a specified problem. They should always reference an issue that describes the problem and contains discussion about the problem itself. Discussion on pull requests should be limited to the pull request itself, i.e. code review.

For more on how 10up writes and manages code, check out our [10up Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/).

### Testing

Helping to test an open source project and provide feedback on success or failure of those tests is also a helpful contribution.  You can find details on the Critical Flows and Test Cases in [this project's GitHub Wiki](https://github.com/10up/wp-content-connect/wiki/Critical-Flows) as well as details on our overall approach to [Critical Flows and Test Cases in our Open Source Best Practices](https://10up.github.io/Open-Source-Best-Practices/testing/#critial-flows).  Submitting the results of testing via our Critical Flows as a comment on a Pull Request of a specific feature or as an Issue when testing the entire project is the best approach for providing testing results.

## Maintenance process

### Triage

Issues and WordPress.org forum posts should be reviewed weekly and triaged as necessary. Not all tasks have to be done at once or by the same person. Triage tasks include:

* Responding to new WordPress.org forum posts and GitHub issues/PRs with an acknolwedgment and following up on existing open/unresolved items that have had movement in the previous week.
* Marking forum posts as resolved when corresponding issues are fixed or as not a support issue if not relevant.
* Creating GitHub issues for WordPress.org forum posts as necessary or linking to them from existing related issues.
* Applying labels and milestones to GitHub issues.

#### Issue labels

All issues should be labeled as bugs (`type:bug`), enhancements/feature requests (`type:enhancement`), or questions/support (`type:question`). Each issue should only be of one "type".

Bugs and enhancements that are closed without a related change should be labeled as `declined`, `duplicate`, or `invalid`. Invalid issues would be where a problem is not reproducible or opened in the wrong repo and should be relatively uncommon. These labels are all prefixed with `closed:`.

There are two other labels that are GitHub defaults with more global meaning we've kept: `good first issue` and `help wanted`.

### Review against WordPress updates

During weekly triage, the tested up to version should be compared against the latest versions of WordPress, both the new and classic editors, and the standalone Gutenberg plugin. If there's a newer version of either, the plugin should be re-tested using any automated tests as well as any manual tests indicated below, and the tested up to version bumped and committed to both GitHub and the WordPress.org repository.

### Release cycle

New releases are targeted based on number and severity of changes along with human availability. When a release is targeted, a due date will be assigned to the appropriate milestone.

### Changesets

Every pull request to `develop` that changes how the plugin behaves needs a changeset. Run `npm run changeset`, pick the bump (patch for fixes, minor for features, major for breaking changes), and write the changelog entry. Pull requests that only touch CI, docs or tests can use the `no changeset` label instead. Don't edit `CHANGELOG.md` or version numbers by hand; the release workflow does both.

Built files (`dist/`) are never committed. Run `npm run build` locally to work on the plugin.

### Release instructions

Releases are automated by `.github/workflows/release.yml`; see `.changeset/README.md` for the details.

1. Prereleases: merging pull requests with changesets into `develop` publishes `X.Y.Z-beta.N` prereleases automatically.
2. Props: update `CREDITS.md` with any new contributors, confirm maintainers are accurate.
3. Readme updates: make any other readme changes as necessary. `README.md` is geared toward GitHub and `readme.txt` contains WordPress.org-specific content. The two are slightly different.
4. Test the latest prerelease zip from the [releases page](https://github.com/10up/wp-content-connect/releases). Ensure the zip has all the files we expect, that it installs and activates correctly and that all basic functionality is working.
5. Release: open a pull request from `develop` into `trunk` (label it `no changeset`) and merge it. The workflow bumps the version, writes the changelog, builds the plugin, commits the build to the `built` branch, tags it `X.Y.Z`, publishes the GitHub release with the zip attached, and merges `trunk` back into `develop`.
6. Close the milestone: edit the [milestone](https://github.com/10up/wp-content-connect/milestone/#) with release date (in the `Due date (optional)` field) and link to the GitHub release (in the `Description` field), then close the milestone.
7. Punt incomplete items: if any open issues or PRs which were milestoned for `X.Y.Z` do not make it into the release, update their milestone to `X.Y.Z+1`, `X.Y+1.0`, `X+1.0.0`, or `Future Release`.

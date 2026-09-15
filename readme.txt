=== WP Content Connect ===
Contributors:      10up, cmmarslender, s3rgiosan, jeffpaul
Tags:
Requires at least: 6.8
Tested up to:      7.0
Stable tag:        2.0.0
Requires PHP:      7.4
License:           GPL-3.0-or-later
License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html

WordPress library that enables direct relationships for posts to posts and posts to users.

== Description ==

WP Content Connect is a WordPress library that enables direct relationships between posts and users. 

This plugin allows developers to define and manage connections between posts and users, facilitating complex content relationships within WordPress. It supports both post-to-post and post-to-user associations, offering customizable options for each relationship. WP Content Connect can be utilized as a standalone library or installed as a plugin, providing flexibility in implementation. Developers can define relationships by hooking into the `tenup-content-connect-init` action, specifying parameters such as post types, unique names, and additional arguments to tailor the connections to specific needs. The plugin also integrates with WordPress queries, enabling the retrieval of related content through a new `relationship_query` parameter for `WP_Query`. This feature allows for sophisticated content retrieval based on defined relationships, enhancing the dynamic capabilities of WordPress sites.  

== Frequently Asked Questions ==

= Where do I report security bugs found in this plugin? =

Please report security bugs found in the source code of the undefined plugin through the [Patchstack Vulnerability Disclosure  Program](https://patchstack.com/database/vdp/4ee25f03-13bc-45ee-8f73-8ee063370dc8). The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

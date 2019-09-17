# WP Content Connect

> WordPress library that enables direct relationships for posts to posts and posts to users.

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Release Version](https://img.shields.io/github/release/10up/wp-content-connect.svg)](https://github.com/10up/wp-content-connect/releases/latest) [![GPLv3 License](https://img.shields.io/github/license/10up/wp-content-connect.svg)](https://github.com/10up/wp-content-connect/blob/master/LICENSE.md)

## Installation and Usage

WP Content Connect can be used as a plugin or a standalone library. The easiest way to use this is to install as a plugin and activate.

### Composer install
If you prefer to use composer, first require it like this:

`$ composer require 10up/wp-content-connect`

or directly in `composer.json`

```
  "require": {
    "10up/wp-content-connect": "^1.5.0",
  }
```

This will install WP Content Connect to your `vendor` folder and allow you to to use it as a library by calling `\TenUp\ContentConnect\Plugin::instance();` from your code.

Alternatively, if you prefer to have composer install it as a plugin, add an entry to your `composer.json` installer paths:
```
  "extra": {
    "installer-paths": {
      "plugins/wp-content-connect/": ["10up/wp-content-connect"],
    }
```


## Defining Relationships
Relationships can be defined once any post types they utilize are defined by hooking into the `tenup-content-connect-init` action. This action is fired on the WordPress `init` action, at priority 100, so any post types must be registered prior to this. Currently supported relationships are post-to-post and post-to-user. Additionally, when registering a relationship, you must specify a `name`. Name enables multiple distinct relationships between the same object types. For instance, you could have a post-to-user relationship for post type `post` with a type of `researchers` to indicate that any user in the "researcher" relationship is a researcher for the post and have another post-to-user relationship defined for post type `post` with a name of `backer` to indicate that any user in the "backer" relationship contributes financially to the post.

### `define_post_to_post( $from, $to, $name, $args = array() )`
This method defines a post to post relationship between two post types, `$from` and `$to`.

#### Parameters:

`$from` (String) First post type in the relationship

`$to` (String|Array) Second post type(s) in the relationship

`$name` (String) Unique name for this relationship, used to distinguish between multiple relationships between the same post types

`$args` (Array) Array of options for the relationship

#### Args:

Args expects options for the `from` and `to` sides of the relationship as top level keys. Options for each direction are as follows:

- `enable_ui` (Bool) - Should the default UI be enabled for the current side of this relationship
- `sortable` (Bool) - Should the relationship be sortable for the current side of this relationship
- `labels` (Array) - Labels used in the UI for the relationship. Currently only expects one value, `name` (String)

#### Return Value

This method returns an instance of `\TenUp\ContentConnect\Relationships\PostToPost` specific to this relationship. The object can then be used to manage related items manually, if required. See the <@TODO insert link> section below.

Example:

```php
function my_define_relationships( $registry ) {
    $args = array(
        'from' => array(
            'enable_ui' => true,
            'sortable' => true,
            'labels' => array(
                'name' => 'Related Tires',
            ),
        ),
        'to' => array(
            'enable_ui' => false,
            'sortable' => false,
            'labels' => array(
                'name' => 'Related Cars',
            ),
        ),
    );

    $relationship = $registry->define_post_to_post( 'car', 'tire', 'car-tires', $args );    
}
add_action( 'tenup-content-connect-init', 'my_define_relationships' );

```

### `define_post_to_user( $post_type, $name $args = array() )`
This method defines a post to user relationship between the supplied post type and users.

#### Parameters:

`$post_type` (String) The post type to be related to users

`$name` (String) Unique name for this relationship, used to distinguish between multiple relationships between users and the same post type

`$args` (Array) Array of options for the relationship

#### Args:

Args expects options for the `from` (post type) side of the relationship as a top level key. Options are as follows:

- `enable_ui` (Bool) - Should the default UI be enabled for the current side of this relationship
- `sortable` (Bool) - Should the relationship be sortable for the current side of this relationship
- `labels` (Array) - Labels used in the UI for the relationship. Currently only expects one value, `name` (String)

#### Return Value

This method returns an instance of `\TenUp\ContentConnect\Relationships\PostToUser` specific to this relationship. The object can then be used to manage related items manually, if required. See the <@TODO insert link> section below.

Example:

```php
function my_define_relationships( $registry ) {
    $args = array(
        'from' => array(
            'enable_ui' => true,
            'sortable' => false,
            'labels' => array(
                'name' => 'Related Users',
            ),
        ),
    )
    
    $relationship = $registry->define_post_to_user( 'post', 'related', $args );   
}
add_action( 'tenup-content-connect-init', 'my_define_relationships' );
```

*There is not currently support for rendering any default UIs on the User side of these relationships*


### Sortable Relationships
Relationships can optionally support sortable related items. Order can be stored independently for both sides of a relationship. For example, if you have cars and tires, you may have a car that has 5 related tires, and if you wanted to sort the tires, you do so from the car page. You could then go to one of the related tires, and order all of the cars it is related to separately.

Since you can manage this relationship from both post types in the relationship, if you added a tire from the car page, and you had relationship data previously stored on the tire, the NEW car in the relationship will still show up in query results, at the very end (after all of your other pre-ordered data).


## Query Integration

Querying for relationships is enabled via a new `relationship_query` parameter for `WP_Query`. The format for `relationship_query` is very similar to `tax_query`.

A valid relationship query segment **requires** `name` and either `related_to_post` OR `related_to_user`. As many relationship segments as necessary can be combined to create a specific set of results, and can be combined using an `AND` or `OR` relation.

#### Top Level Args:

- `relation` (String) Can be either `AND` (default) or `OR`. How all of the segments in the relationship should be combined.

#### Segment Args:

- `name` (String) The unique name for the relationship you are querying. Should match a `name` from registering relationships.
- `related_to_post` (Int) Find items in the relationship related to this post ID. Cannot be used in the same segment as `related_to_user`.
- `related_to_user` (Int) Find items in the relationship related to this user ID. Cannot be used in the same segment as `related_to_post`.

Example:

```php
$query = new WP_Query( array(
    'post_type' => 'post',
    'relationship_query' => array(
        'relation' => 'AND', // AND is default
        array(
            'related_to_post' => 25,
            'name' => 'related',
        ),
        array(
            'related_to_user' => 5,
            'name' => 'researcher',
        )
    ),
) );
```

Currently, querying for multiple post types in WP_Query may not work as expected. When using relationship queries, make sure to only have one `post_type` value in WP_Query.

#### Order By

For relationships where sorting is disabled, all of the default WP_Query `orderby` options are supported.
In addition to default `orderby` options, if sorting is enabled for a relationship, an additional orderby parameter `relationship` is supported.
When using `relationship` as the orderby value, the order is always `ASC` and you must adhere to the following `WP_Query` and `WP_User_Query` restrictions:

- Compound relationship queries are not allowed - only one segment may be added to the query

For example, this is fine:

```php
'relationship_query' => array(
    array(
        'related_to_post' => 25,
        'name' => 'related',
    ),
),
'orderby' => 'relationship',
```

while this will not work (orderby will be ignored):
```php
'relationship_query' => array(
    array(
        'related_to_post' => 25,
        'name' => 'related',
    ),
    array(
		'related_to_post' => 15,
		'name' => 'related',
	),
),
'orderby' => 'relationship',
```

## Manually Managing Relationships

If you choose to not use the built in UIs for relationships, you'll need to manually update relationships. **DO NOT** try and work directly with the database tables. Instead, work with the following API methods. The underlying implementations may need to change from time to time, but the following methods should continue to function if the underlying implementations need to change.

These methods are available on the relationship objects returned when defining the relationship. Make sure to call these methods on the specific relationship object you are defining a relationship for, as these methods are specific to the relationship context (they are aware of the `name` of the relationship, as well as the post types in the relationship).

If you don't already have a relationship object, you can get one from the registry using either `Registry->get_post_to_post_relationship()` or `Registry->get_post_to_user_relationship()`.

### `Registry->get_post_to_post_relationship( $cpt1, $cpt2, $name )`
Returns the relationship object between the two post types with the provided name.

#### Parameters:

`$cpt1` (String) The first post type in the relationship 
 
`$cpt2` (String) The second post type in the relationship

`$name` (String) The name of the relationship, as passed to define_post_to_post_relationship

#### Example:

```php
$registry = \TenUp\ContentConnect\Plugin::instance()->get_registry();

// Gets the car to tire relationship defined in the example above
$relationship = $registry->get_post_to_post_relationship( 'car', 'tire', 'car-tires' );
```

### `Registry->get_post_to_user_relationship( $post_type, $name )`
Returns the relationship object between the post types and users with the provided name.

#### Parameters:

`$post_type` (String) The post type in the post to user relationship 

`$name` (String) The name of the relationship, as passed to define_post_to_user_relationship

#### Example:

```php
$registry = \TenUp\ContentConnect\Plugin::instance()->get_registry();

// Gets the post to user relationship defined in the example above
$relationship = $registry->get_post_to_user_relationship( 'post', 'related' );
```

### `PostToPost->add_relationship( $pid1, $pid2 )`
This method adds a relationship between one post and another, in a post to post relationship. When calling this method, the order of IDs passed is not important.

#### Parameters:

`$pid1` (Int) The ID of the first post in the relationship

`$pid2` (Int) The ID of the second post in the relationship

#### Example:

```php
// $relationship is the return value from ->define_post_to_post()
$relationship->add_relationship( 1, 2 ); // Adds a relationship between post ID 1 and post ID 2
```

### `PostToPost->delete_relationship( $pid1, $pid2 )`
This methods deletes a relationship between one post and another, in a post to post relationship. When calling this method, the order of IDs passed is not important. 

#### Parameters:

`$pid1` (Int) The ID of the first post in the relationship. Does **not** need to be in the same order as the relationship was added.

`$pid2` (Int) The ID of the second post in the relationship. Does **not** need to be in the same order as the relationship was added.

#### Example:
```php
// $relationship is the return value from ->define_post_to_post()
// Note that the example above added these in the reverse order, but the relationship is still deleted
$relationship->delete_relationship( 2, 1 ); // Deletes the relationship between post ID 1 and post ID 2. 
```

### `PostToPost->replace_relationships( $post_id, $related_ids )`
Replaces existing relationships for the post to post relationship. Any relationship that is present in the database but not in $related_ids will no longer be related.

#### Parameters:

`$post_id` (Int) The ID of the post we are replacing relationships from.

`$related_ids` (Array) An array of Post IDs of items related to $post_id

#### Example:

Post ID 5 is related to posts 2, 3, 6, 7, 8

```php
// $relationship is the return value from ->define_post_to_post()
$relationship->replace_relationships( 5, array( 2, 3, 6, 7, 8 ) );
```

### `PostToPost->save_sort_data( $object_id, $ordered_ids )`
For a relationship with sorting enabled, this saves the order of the posts for a single direction of the relationship.

#### Parameters:

`$object_id` (Int) The Post ID that we are ordering from. If we were ordering 5 tires for a single car, this would be the car ID.

`$ordered_ids` (Array) An array of Post IDs, in the order they should be sorted. If we were ordering 5 tires for a single car, this is the ordered tire IDs.

#### Example:

Car ID 5 has five related tires, that should be ordered 7, 6, 3, 8, 2

```php
// $relationship is the return value from ->define_post_to_post()
$relationship->save_sort_data( 5, array( 7, 6, 3, 8, 2 ) );
```

### `PostToUser->add_relationship( $post_id, $user_id )`
This method adds a relationship between a post and a user, in a post to user relationship. 

#### Parameters:

`$post_id` (Int) The ID of the post in the relationship

`$user_id` (Int) The ID of the user in the relationship

#### Example:

```php
// $relationship is the return value from ->define_post_to_user()
$relationship->add_relationship( 1, 5 ); // Adds a relationship between post 1 and user 5
```

### `PostToUser->delete_relationship( $post_id, $user_id )` 
This method deletes a relationship between a post and a user, in a post to user relationship.

#### Parameters:

`$post_id` (Int) The ID of the post in the relationship

`$user_id` (Int) The ID of the user in the relationship

#### Example:

```php
// $relationship is the return value from ->define_post_to_user()
$relationship->delete_relationship( 1, 5 ); // Deletes the relationship between post 1 and user 5
```

### `PostToUser->replace_post_to_user_relationships( $post_id, $user_ids )`
Replaces users related to a post with the provided set of user ids. Any users related to the post that are not provided in $user_ids will no longer be related.

#### Parameters:

`$post_id` (Int) The ID of the post we are replacing relationships from.

`$user_ids` (Array) An array of User IDs related to $post_id

#### Example:

Post ID 5 is related to users 3, 4, 5

```php
// $relationship is the return value from ->define_post_to_user()
$relationship->replace_post_to_user_relationships( 5, array( 3, 4, 5 ) );
```

### `PostToUser->replace_user_to_post_relationships( $user_id, $post_ids )`
Replaces posts related to a user with the provided set of post ids. Any posts related to the user that are not provided in $post_ids will no longer be related.

#### Parameters:

`$user_id` (Int) The User ID we are replacing relationships from.

`$post_ids` (Array) An array of Post IDs related to $user_id

#### Example:

User 2 is related to posts 6, 7, 8

```php
// $relationship is the return value from ->define_post_to_user()
$relationship->replace_user_to_post_relationships( 2, array( 6, 7, 8 ) );
```

### `PostToUser->save_post_to_user_sort_data( $object_id, $ordered_user_ids )`
For a relationship with sorting enabled, this saves the order of users for a particular post

#### Parameters:

`$object_id` (Int) The ID of the post to store the order of users for

`$ordered_user_ids` (Array) Array of User IDs, in the order they should be sorted.

#### Example:

Post ID has 5 users that need to be stored in the following order: 2, 4, 1, 6, 3

```php
// $relationship is the return value from ->define_post_to_user()
$relationship->save_post_to_user_sort_data( 5, array( 2, 4, 1, 6, 3 ) );
```

### `PostToUser->save_user_to_post_sort_data( $user_id, $ordered_post_ids )` 
For a relationship with sorting enabled, this saves the order of posts for a particular user

#### Parameters:

`$user_id` (Int) The ID of the user to store the order of posts for

`$ordered_post_ids` (Array) Array of Post IDs, in the order they should be sorted

#### Example:

User ID 1 has 5 posts that need to be stored in the following order: 4, 2, 7, 9, 8

```php
// $relationship is the return value from ->define_post_to_user()
$relationship->save_user_to_post_sort_data( 1, array( 4, 2, 7, 9, 8 ) );
```

## Support Level

**Active:** 10up is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.

## Like what you see?

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>

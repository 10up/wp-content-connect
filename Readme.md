# WP Content Connect

WordPress library that enables direct relationships for posts to posts and posts to users.

## Usage

1. `composer install --no-dev`

1. `yarn install`

1. include with composer, install as a plugin and activate, or load the main `content-connect.php` file

## Defining Relationships
Relationships can be defined once any post types they utilize are defined by hooking into the `tenup-content-connect-init` action. This action is fired on the WordPress `init` action, at prority 100, so any post types must be registered prior to this. Currently supported relationships are post-to-post and post-to-user. Additionally, when registering a relationship, you must specify a `name`. Name enables multiple distinct relationships between the same object types. For instance, you could have a post-to-user relationship for post type `post` with a type of `researchers` to indicate that any user in the "researcher" relationship is a researcher for the post and have another post-to-user relationship defined for post type `post` with a name of `backer` to indicate that any user in the "backer" relationship contributes financially to the post.

### `define_post_to_post( $from, $to, $name, $args = array() )`
This method defines a post to post relationship between two post types, `$from` and `$to`.

#### Parameters:

`$from` (String) First post type in the relationship

`$to` (String) Second post type in the relationship

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

### `PostToUser->delete_relationship( $post_id, $user_id )` 

### `PostToUser->save_post_to_user_sort_data( $object_id, $ordered_user_ids )`

### `PostTo_user->save_user_to_post_sort_data( $user_id, $ordered_post_ids )` 

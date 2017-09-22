# WP Content Connect

WordPress library that enables direct relationships for posts to posts and posts to users.

## Usage

1. `composer install --no-dev`

1. `yarn install`

1. include with composer, install as a plugin and activate, or load the main `content-connect.php` file

## Defining Relationships
Relationships can be defined once any post types they utilize are defined by hooking into the `tenup-content-connect-init` action. This action is fired on the WordPress `init` action, at prority 100, so any post types must be registered prior to this. Currently supported relationships are post-to-post and post-to-user. Additionally, when registering a relationship, you must specify a `type`. Type enables multiple distinct relationships between the same object types. For instance, you could have a post-to-user relationship for post type `post` with a type of `researchers` to indicate that any user in the "researcher" relationship is a researcher for the post and have another post-to-user relationship defined for post type `post` with a type of `backer` to indicate that any user in the "backer" relationship contributes fincially to the post.

### `define_post_to_post( $from, $to, $type, $args = array() )`
This method defines a post to post relationship between two post types, `$from` and `$to`.

#### Parameters:

`$from` (String) First post type in the relationship

`$to` (String) Second post type in the relationship

`$type` (String) Unique name for this relationship, used to distinguish between multiple relationships between the same post types

`$args` (Array) Array of options for the relationship

#### Args:

Args expects options for the `from` and `to` sides of the relationship as top level keys. Options for each direction are as follows:

- `enable_ui` (Bool) - Should the default UI be enabled for the current side of this relationship
- `sortable` (Bool) - Should the relationship be sortable for the current side of this relationship
- `labels` (Array) - Labels used in the UI for the relationship. Currently only expects one value, `name` (String)

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

    $registry->define_post_to_post( 'car', 'tire', 'car-tires', $args );    
}
add_action( 'tenup-content-connect-init', 'my_define_relationships' );

```

### `define_post_to_user( $post_type, $type $args = array() )`
This method defines a post to user relationship between the supplied post type and users.

#### Parameters:

`$post_type` (String) The post type to be related to users

`$type` (String) Unique name for this relationship, used to distinguish between multiple relationships between users and the same post type

`$args` (Array) Array of options for the relationship

#### Args:

Args expects options for the `from` (post type) side of the relationship as a top level key. Options are as follows:

- `enable_ui` (Bool) - Should the default UI be enabled for the current side of this relationship
- `sortable` (Bool) - Should the relationship be sortable for the current side of this relationship
- `labels` (Array) - Labels used in the UI for the relationship. Currently only expects one value, `name` (String)

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
    
    $registry->define_post_to_user( 'post', 'related', $args );   
}
add_action( 'tenup-content-connect-init', 'my_define_relationships' );
```

*There is not currently support for rendering any default UIs on the User side of these relationships*


### Sortable Relationships
Relationships can optionally support sortable related items. Sorting data is stored on the post object, not on the relationship. This means that you can store an order on both sides of any given relationship independent of one another with both UIs enabled. For example, if you have cars and tires, you may have a car that has 5 related tires, and if you wanted to sort the tires, you do so from the car page. You could then go to one of the related tires, and order all of the cars it is related to separately.

Since you can manage this relationship from both post types in the relationship, if you added a tire from the car page, and you had relationship data previously stored on the tire, the NEW car in the relationship will still show up in query results, at the very end (after all of your other pre-ordered data).


## Query Integration

Querying for relationships is enabled via a new `relationship_query` parameter for `WP_Query`. The format for `relationship_query` is very similar to `tax_query`.

A valid relationship query segment **requires** `type` and either `related_to_post` OR `related_to_user`. As many relationship segments as necessary can be combined to create a specific set of results, and can be combined using an `AND` or `OR` relation.

#### Top Level Args:

- `relation` (String) Can be either `AND` (default) or `OR`. How all of the segments in the relationship should be combined.

#### Segment Args:

- `type` (String) The unique type for the relationship you are querying. Should match a `type` from registering relationships.
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
            'type' => 'related',
        ),
        array(
            'related_to_user' => 5,
            'type' => 'researcher',
        )
    ),
) );
```

Currently, querying for multiple post types in WP_Query will not work. When using relationship queries, make sure to only have one `post_type` value in WP_Query.

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
        'type' => 'related',
    ),
),
'orderby' => 'relationship',
```

while this will not work (orderby will be ignored):
```php
'relationship_query' => array(
    array(
        'related_to_post' => 25,
        'type' => 'related',
    ),
    array(
		'related_to_post' => 15,
		'type' => 'related',
	),
),
'orderby' => 'relationship',
```


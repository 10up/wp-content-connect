Post to Post Library
--------------------

Allows creating relationships directly between posts.

Querying with multiple post IDs or multiple post types in WP_Query will not work!


### Defining Relationships


#### Sortable Relationships
Relationships can optionally support sortable related items. When sorting is enabled, a UI can only be displayed on
one end of the relationship (to avoid issues with unpredictable sorting when a post is added or deleted from the other 
side of the relationship ).


### Query Integration

#### Order By

For relationships where sorting is disabled, all of the default orderby options for WP_Query are supported.
In addition to default orderby options, if sorting is enabled for a relationship, an additional orderby parameter `relationship` is supported.
When using `relationship` as the orderby value, the order is always `ASC` and you must adhere to the following `WP_Query` and `WP_User_Query` restrictions:

- Compound relationship queries are not allowed - only one segment may be added to the query
For example, this is fine:
```php
'relationship_query' => array(
    array(
        'related_to_post' => 25,
        'type' => 'related',
    ),
)
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
)
```


Post to Post Library
--------------------

Allows creating relationships directly between posts.

Querying with multiple post IDs or multiple post types in WP_Query will not work!


### Defining Relationships


#### Sortable Relationships
Relationships can optionally support sortable related items. Sorting data is stored on the post object, not on the relationship. This means that you can store an order on both sides of any given relationship independent of one another with both UIs enabled. For example, if you have cars and tires, you may have a car that has 5 related tires, and if you wanted to sort the tires, you do so from the car page. You could then go to one of the related tires, and order all of the cars it is related to separately.

Since you can manage this relationship from both post types in the relationship, if you added a tire from the car page, and you had relationship data previously stored on the tire, the NEW car in the relationship will still show up in query results, at the very end (after all of your other pre-ordered data).


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


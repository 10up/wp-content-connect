# WP Content Connect - Architecture Documentation

**Version:** 2.0.0
**Status:** Stable
**UI Framework:** React (WordPress Block Editor components)

## Introduction

WP Content Connect is a WordPress library that enables direct relationships between:

- **Posts to Posts** - Connect any post type to any other post type
- **Posts to Users** - Connect posts to WordPress users

The plugin provides:

- Custom database tables for efficient relationship storage
- Integration with `WP_Query` and `WP_User_Query` via `relationship_query` parameter
- Admin UI for managing relationships in both Block Editor and Classic Editor
- REST API V2 for CRUD operations on relationships
- WordPress Data Store for state management
- Helper functions for programmatic access
- JavaScript filter hooks for UI customization

## Directory Structure

```txt
wp-content-connect/
├── .github/                         # GitHub workflows
├── assets/
│   └── js/
│       ├── index.ts                 # Block Editor entry point
│       ├── classic-editor.tsx       # Classic Editor entry point
│       ├── store/                   # WordPress Data Store
│       │   ├── index.ts             # Store definition
│       │   ├── types.ts             # TypeScript types
│       │   └── api.ts               # REST API wrapper functions
│       ├── hooks/                   # React hooks
│       │   ├── use-relationships.ts # Relationship data hook
│       │   └── use-related-entities.ts # Related entities hook
│       └── components/              # React components
│           ├── relationships-panel.tsx  # Block Editor sidebar panel
│           └── relationship-manager.tsx # Content picker UI
├── dist/                            # Compiled assets (generated)
│   └── js/
│       ├── block-editor.js          # Block Editor bundle
│       ├── block-editor.asset.php   # Asset dependencies
│       ├── classic-editor.js        # Classic Editor bundle
│       └── classic-editor.asset.php # Asset dependencies
├── includes/
│   ├── API/
│   │   ├── V1/
│   │   │   └── Search.php           # Legacy REST API search (deprecated)
│   │   └── V2/                      # New REST API
│   │       ├── AbstractRoute.php    # Base route class
│   │       ├── Route/
│   │       │   └── Relationships.php # Global relationships endpoint
│   │       └── Post/Route/
│   │           ├── AbstractPostRoute.php # Post-specific base class
│   │           ├── Relationships.php     # Post relationships endpoint
│   │           └── RelatedEntities.php   # Related entities CRUD
│   ├── QueryIntegration/
│   │   ├── RelationshipQuery.php    # WP_Query relationship parser
│   │   ├── UserRelationshipQuery.php # WP_User_Query relationship parser
│   │   ├── UserQueryIntegration.php # WP_User_Query hooks
│   │   └── WPQueryIntegration.php   # WP_Query hooks
│   ├── Relationships/
│   │   ├── DeletedItems.php         # Cleanup on post/user deletion
│   │   ├── PostToPost.php           # Post-to-post relationship logic
│   │   ├── PostToUser.php           # Post-to-user relationship logic
│   │   └── Relationship.php         # Abstract base class
│   ├── Tables/
│   │   ├── BaseTable.php            # Abstract table class
│   │   ├── PostToPost.php           # Post-to-post table schema
│   │   └── PostToUser.php           # Post-to-user table schema
│   ├── UI/
│   │   ├── BlockEditor.php          # Block Editor asset enqueuing
│   │   └── ClassicEditor.php        # Classic Editor metaboxes
│   ├── Helpers.php                  # Helper functions
│   ├── Plugin.php                   # Main plugin class (singleton)
│   ├── Registry.php                 # Relationship registry
│   └── REST.php                     # REST API permission checks
├── tests/                           # PHPUnit tests
│   └── php/
│       ├── Helpers/                 # Helper function tests
│       └── API/V2/                  # REST API tests
├── autoload.php                     # PSR-4 autoloader
├── content-connect.php              # Main plugin file
├── composer.json                    # PHP dependencies
└── package.json                     # JS dependencies (React, WordPress packages)
```

## Core Architecture

### Plugin Initialization Flow

```txt
content-connect.php
    │
    ▼
Plugin::instance() (singleton)
    │
    ├──► Constants: CONTENT_CONNECT_VERSION, CONTENT_CONNECT_URL, CONTENT_CONNECT_PATH
    │
    ├──► Table registration
    │       └──► admin_init hook → dbDelta() schema updates
    │
    ├──► Registry instantiation
    │
    ├──► Module setup (all implement setup() method)
    │       ├──► WPQueryIntegration (posts_where, posts_join, etc.)
    │       ├──► UserQueryIntegration (pre_user_query)
    │       ├──► ClassicEditor (add_meta_boxes, admin_enqueue_scripts)
    │       ├──► BlockEditor (enqueue_block_editor_assets)
    │       ├──► DeletedItems (deleted_post, deleted_user hooks)
    │       ├──► REST (rest_api_init for permission checks)
    │       ├──► API\V1\Search (legacy search endpoint)
    │       ├──► API\V2\Route\Relationships
    │       ├──► API\V2\Post\Route\Relationships
    │       └──► API\V2\Post\Route\RelatedEntities
    │
    └──► do_action('tenup-content-connect-init') @ priority 100
            └──► Developers define relationships here
```

### Component Responsibilities

| Component | Responsibility |
|-----------|---------------|
| `Plugin` | Bootstrap, dependency injection, module orchestration |
| `Registry` | Stores and retrieves relationship definitions |
| `Relationships/*` | Business logic for adding/removing/querying relationships |
| `Tables/*` | Database schema, CRUD operations |
| `QueryIntegration/*` | Hooks into WP_Query and WP_User_Query |
| `UI/BlockEditor` | Enqueues Block Editor React components |
| `UI/ClassicEditor` | Renders metaboxes for Classic Editor |
| `API/V1/Search` | Legacy REST endpoint (deprecated) |
| `API/V2/*` | Modern REST API with full CRUD |
| `Helpers` | Utility functions for programmatic access |

## Database Schema

### Table: `{prefix}post_to_post`

Stores post-to-post relationships with **bidirectional storage** (both directions are stored).

```sql
CREATE TABLE {prefix}post_to_post (
    id1 bigint(20) unsigned NOT NULL,
    id2 bigint(20) unsigned NOT NULL,
    name varchar(64) NOT NULL,
    order int(11) NOT NULL default 0,
    UNIQUE KEY id1_id2_name (id1, id2, name),
    KEY id2_name (id2, name),
    KEY id2_name_order (id2, name, order)
);
```

**Schema Version:** 0.1.10

**Columns:**

- `id1` - Source post ID
- `id2` - Target post ID
- `name` - Relationship name identifier
- `order` - Sort order of `id2` when viewed from `id1`

**Storage Strategy:**
When connecting Post A to Post B, two rows are created:

1. `id1=A, id2=B, name=rel, order=X` (A's view of B)
2. `id1=B, id2=A, name=rel, order=Y` (B's view of A)

### Table: `{prefix}post_to_user`

Stores post-to-user relationships with **unidirectional storage** but separate order columns.

```sql
CREATE TABLE {prefix}post_to_user (
    post_id bigint(20) unsigned NOT NULL,
    user_id bigint(20) unsigned NOT NULL,
    name varchar(64) NOT NULL,
    user_order int(11) NOT NULL default 0,
    post_order int(11) NOT NULL default 0,
    UNIQUE KEY post_user_name (post_id, user_id, name),
    KEY user_name (user_id, name),
    KEY user_name_order (user_id, name, post_order),
    KEY post_name (post_id, name),
    KEY post_name_order (post_id, name, user_order)
);
```

**Schema Version:** 0.1.10

**Columns:**

- `post_id` - Post ID
- `user_id` - User ID
- `name` - Relationship name identifier
- `user_order` - Order of users when viewing from a post
- `post_order` - Order of posts when viewing from a user

### Index Strategy

Indexes are designed for common query patterns:

- `id1_id2_name` / `post_user_name` - Prevents duplicates, fast lookups
- `id2_name` / `user_name` / `post_name` - Queries without ordering
- `id2_name_order` / `*_order` - Queries with ORDER BY relationship

## PHP Class Reference

### Plugin.php

Main plugin class using singleton pattern.

```php
namespace TenUp\ContentConnect;

class Plugin {
    public $tables = [];      // Table instances ('p2p', 'p2u')
    public $registry;         // Registry instance

    public static function instance();        // Get singleton instance
    public function get_registry();           // Get Registry instance
    public function get_table($table_name);   // Get table instance ('p2p' or 'p2u')
}
```

**Constants Defined:**

- `CONTENT_CONNECT_VERSION` - Plugin version
- `CONTENT_CONNECT_URL` - Plugin URL
- `CONTENT_CONNECT_PATH` - Plugin filesystem path

**Key Hooks:**

- `init` @ priority 100 - Fires `tenup-content-connect-init`
- `admin_init` - Runs table schema upgrades

### Registry.php

Central registry for all relationship definitions.

```php
namespace TenUp\ContentConnect;

class Registry {
    // Define a post-to-post relationship
    public function define_post_to_post($from, $to, $name, $args = []);

    // Define a post-to-user relationship
    public function define_post_to_user($post_type, $name, $args = []);

    // Retrieve relationships
    public function get_post_to_post_relationship($from, $to, $name);
    public function get_post_to_user_relationship($post_type, $name);
    public function get_post_to_post_relationship_by_key($key);
    public function get_post_to_user_relationship_by_key($key);

    // Get all relationships
    public function get_post_to_post_relationships();
    public function get_post_to_user_relationships();
}
```

**Relationship Key Format:** `{from}_{to}_{name}`

**Arguments ($args):**

- `enable_from_ui` (bool) - Show UI on "from" post type edit screen
- `enable_to_ui` (bool) - Show UI on "to" post type edit screen
- `from_labels` (array) - Labels for "from" side UI
- `to_labels` (array) - Labels for "to" side UI
- `from_sortable` (bool) - Enable drag-and-drop on "from" side
- `to_sortable` (bool) - Enable drag-and-drop on "to" side
- `from_max_items` (int) - Maximum items on "from" side (0 = unlimited)
- `to_max_items` (int) - Maximum items on "to" side (0 = unlimited)

### Helpers.php

Helper functions for programmatic access to relationship data.

```php
namespace TenUp\ContentConnect\Helpers;

// Get Plugin singleton
function get_plugin();

// Get Registry instance
function get_registry();

// Get related IDs by relationship name
function get_related_ids_by_name($post_id, $relationship_name);

// Query post-to-post relationships by field
// $field: 'any', 'key', 'post_type', 'from', 'to'
function get_post_to_post_relationships_by($field = 'any', $value = '');

// Query post-to-user relationships by field
// $field: 'any', 'key', 'post_type'
function get_post_to_user_relationships_by($field = 'any', $value = '');

// Get relationship data for a post
// $rel_type: 'any', 'post-to-post', 'post-to-user'
// $context: 'view' (metadata only) or 'embed' (includes related entities)
function get_post_relationships_data($post, $rel_type = 'any', $other_post_type = false, $context = 'view');

// Get post-to-post relationship data
function get_post_to_post_relationships_data($post, $other_post_type = false, $context = 'view');

// Get post-to-user relationship data
function get_post_to_user_relationships_data($post, $context = 'view');
```

### Relationships/Relationship.php (Abstract)

Base class for relationship types.

```php
namespace TenUp\ContentConnect\Relationships;

abstract class Relationship {
    public $name;
    public $id;
    public $enable_from_ui;
    public $enable_to_ui;
    public $from_labels;
    public $to_labels;
    public $from_sortable;
    public $to_sortable;
    public $from_max_items;
    public $to_max_items;
}
```

### Relationships/PostToPost.php

Post-to-post relationship implementation.

```php
namespace TenUp\ContentConnect\Relationships;

class PostToPost extends Relationship {
    public $from;  // Post type
    public $to;    // Post type (can be array)

    // Get related post IDs
    public function get_related_object_ids($post_id, $order_by_relationship = false);

    // Add relationship (stores both directions)
    public function add_relationship($pid1, $pid2);

    // Delete relationship (removes both directions)
    public function delete_relationship($pid1, $pid2);

    // Replace all relationships for a post
    public function replace_relationships($post_id, $related_ids);

    // Save sort order
    public function save_sort_data($object_id, $ordered_ids);
}
```

### Relationships/PostToUser.php

Post-to-user relationship implementation.

```php
namespace TenUp\ContentConnect\Relationships;

class PostToUser extends Relationship {
    public $post_type;

    // Get related IDs
    public function get_related_post_ids($user_id, $order_by_relationship = false);
    public function get_related_user_ids($post_id, $order_by_relationship = false);

    // Add/delete relationships
    public function add_relationship($post_id, $user_id);
    public function delete_relationship($post_id, $user_id);

    // Replace operations
    public function replace_post_to_user_relationships($post_id, $user_ids);
    public function replace_user_to_post_relationships($user_id, $post_ids);

    // Sort operations
    public function save_post_to_user_sort_data($post_id, $ordered_user_ids);
    public function save_user_to_post_sort_data($user_id, $ordered_post_ids);
}
```

### Tables/BaseTable.php (Abstract)

Base class for custom database tables.

```php
namespace TenUp\ContentConnect\Tables;

abstract class BaseTable {
    public $db_version;      // Schema version string
    public $table_name;      // Table name without prefix

    public function get_table_name();         // Get full table name with prefix
    abstract public function get_schema();    // Get current schema SQL
    public function maybe_upgrade_table();    // Runs dbDelta if version changed
    public function replace($data);           // INSERT ... ON DUPLICATE KEY UPDATE
    public function replace_bulk($rows);      // Bulk replace
    public function delete($where);           // DELETE with WHERE
}
```

### UI/BlockEditor.php

Enqueues Block Editor assets.

```php
namespace TenUp\ContentConnect\UI;

class BlockEditor {
    public function setup();                    // Hooks enqueue_block_editor_assets
    public function enqueue_block_editor_assets(); // Enqueues block-editor.js
}
```

### UI/ClassicEditor.php

Manages Classic Editor metaboxes with React components.

```php
namespace TenUp\ContentConnect\UI;

class ClassicEditor {
    public function setup();                    // Hooks admin_enqueue_scripts, add_meta_boxes
    public function enqueue_classic_editor_assets($hook_suffix);
    public function add_relationships_meta_boxes($post_type, $post);
    public function render_relationship_meta_box($post, $args);
}
```

**Metabox Container:**
Renders a `<div>` with data attributes for React to mount:

- `data-content-connect` - Marker attribute
- `data-post-id` - Current post ID
- `data-relationship` - JSON-encoded relationship data

## REST API

### V2 Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/content-connect/v2/relationships` | List all relationship definitions |
| GET | `/content-connect/v2/post/{id}/relationships` | Get relationships for a post |
| GET | `/content-connect/v2/post/{id}/related` | List related entities |
| POST | `/content-connect/v2/post/{id}/related` | Replace all relationships |
| PUT | `/content-connect/v2/post/{id}/related` | Add single relationship |
| DELETE | `/content-connect/v2/post/{id}/related` | Remove single relationship |

### API\V2\AbstractRoute.php

Base class for V2 REST routes.

```php
namespace TenUp\ContentConnect\API\V2;

abstract class AbstractRoute {
    protected $namespace = 'content-connect/v2';

    abstract public function setup();
    abstract public function register_routes();

    protected function get_post($post_id);   // Validates and returns WP_Post
    protected function get_user($user_id);   // Validates and returns WP_User
}
```

### API\V2\Route\Relationships.php

Global relationships discovery endpoint.

**Endpoint:** `GET /content-connect/v2/relationships`

**Parameters:**

- `rel_type` (string) - Filter by type: 'post-to-post' or 'post-to-user'
- `filter_by` (string) - Field to filter: 'post_type', 'from', 'to'
- `filter_value` (string) - Value to match

### API\V2\Post\Route\Relationships.php

Post-specific relationships endpoint.

**Endpoint:** `GET /content-connect/v2/post/{id}/relationships`

**Parameters:**

- `id` (int, required) - Post ID
- `rel_type` (string) - Filter by relationship type
- `post_type` (string) - Filter by related post type
- `context` (string) - 'view' or 'embed'

### API\V2\Post\Route\RelatedEntities.php

CRUD operations for related entities.

**Endpoints:**

```
GET    /content-connect/v2/post/{id}/related?rel_key=X&rel_type=Y
POST   /content-connect/v2/post/{id}/related  (body: {rel_key, rel_type, related_ids[]})
PUT    /content-connect/v2/post/{id}/related  (body: {rel_key, rel_type, related_id})
DELETE /content-connect/v2/post/{id}/related  (body: {rel_key, rel_type, related_id})
```

**Response Format:**

```json
{
    "ID": 123,
    "id": 123,
    "name": "Post Title",
    "type": "post",
    "uuid": "abc123..."
}
```

**Pagination Headers:**

- `X-WP-Total` - Total items
- `X-WP-TotalPages` - Total pages

### V1 Legacy Endpoint (Deprecated)

> **Deprecated since 2.0.0:** Use REST API V2 endpoints instead.

**Endpoint:** `POST /content-connect/v1/search`

**Parameters:**

- `nonce` - Security nonce
- `object_type` - 'post' or 'user'
- `post_type` - Post type(s) to search
- `search` - Search term
- `paged` - Page number
- `relationship_name` - Relationship identifier
- `current_post_id` - Current post being edited

## JavaScript Architecture

### Technology Stack

- React (via WordPress packages)
- TypeScript
- WordPress Data API (@wordpress/data)
- @10up/block-components (ContentPicker)
- 10up-toolkit (build system)

### Build Configuration

```json
{
  "10up-toolkit": {
    "entry": {
      "block-editor": "assets/js/index.ts",
      "classic-editor": "assets/js/classic-editor.tsx"
    }
  }
}
```

### WordPress Data Store

**Store Name:** `wp-content-connect`

#### State Shape

```typescript
type ContentConnectState = {
    relationships: {
        [postId: number]: ContentConnectRelationships;
    };
    relatedEntities: {
        [key: string]: ContentConnectRelatedEntities;
    };
    dirtyEntityIds: Set<number>;
};
```

#### Selectors

| Selector | Parameters | Returns |
|----------|------------|---------|
| `getRelationships` | `postId, options?` | Relationship definitions |
| `getRelatedEntities` | `postId, options` | Array of related entities |
| `getDirtyEntityIds` | - | Array of post IDs with unsaved changes |

#### Actions

| Action | Parameters | Purpose |
|--------|------------|---------|
| `setRelationships` | `postId, relationships` | Set relationship definitions |
| `setRelatedEntities` | `key, entities` | Set related entities for a key |
| `updateRelatedEntities` | `postId, relKey, relType, entities` | Update and mark as dirty |
| `markPostAsDirty` | `postId` | Mark post as having unsaved changes |
| `clearDirtyEntities` | - | Clear all dirty flags |

#### Resolvers

Resolvers automatically fetch data from REST API when selectors are called:

```typescript
// When getRelationships is called, if data not in store:
GET /content-connect/v2/post/{postId}/relationships

// When getRelatedEntities is called, if data not in store:
GET /content-connect/v2/post/{postId}/related?rel_key=X&rel_type=Y
```

#### Auto-Persistence

The store registers a filter on `editor.preSavePost` that:

1. Checks for dirty entity IDs
2. For each dirty post, iterates relationships
3. Calls REST API to persist changes
4. Clears dirty flags on success

### React Hooks

#### useRelationships

```typescript
function useRelationships(
    postId: number,
    options?: GetRelationshipsOptions
): [hasResolved: boolean, relationships: ContentConnectRelationships]
```

#### useRelatedEntities

```typescript
function useRelatedEntities(
    postId: number,
    options: GetRelatedEntitiesOptions
): [
    hasResolved: boolean,
    entities: ContentConnectRelatedEntities,
    updateFn: (entities: ContentConnectRelatedEntities) => void
]
```

### React Components

#### RelationshipsPanel (Block Editor)

Registered as a Gutenberg plugin that renders Document Settings panels.

```tsx
// Renders one panel per relationship with enable_ui: true
<PluginDocumentSettingPanel
    name={`content-connect-${relationship.rel_key}`}
    title={relationship.labels.name}
>
    <RelationshipManager relationship={relationship} />
</PluginDocumentSettingPanel>
```

#### RelationshipManager

Wrapper around `@10up/block-components` ContentPicker.

**Props:**

- `relationship` - Relationship definition object

**Features:**

- Configures ContentPicker based on relationship settings
- Handles post-to-post and post-to-user modes
- Supports sortable (drag-and-drop)
- Applies JavaScript filter hooks

**CSS Classes:**

- `content-connect-relationship-manager`
- `content-connect-relationship-manager-{rel_name}`
- `content-connect-relationship-manager-{rel_key}`

### JavaScript Filter Hooks

Filters for customizing the UI behavior:

| Filter | Signature | Purpose |
|--------|-----------|---------|
| `contentConnect.searchResultFilter` | `(defaultFilter, context) => filterFn` | Customize search results |
| `contentConnect.pickedItemFilter` | `(defaultFilter, context) => filterFn` | Customize picked items |
| `contentConnect.pickedItemPreviewComponent` | `(Component, context) => Component` | Replace preview component |

**Context Object:**

```typescript
{
    rel_key: string;      // Relationship key
    rel_type: string;     // 'post-to-post' or 'post-to-user'
    postId: number;       // Current post ID
    mode: string;         // 'post' | 'user' | 'term'
}
```

**Example:**

```javascript
import { addFilter } from '@wordpress/hooks';

addFilter(
    'contentConnect.searchResultFilter',
    'my-plugin/add-post-id',
    (defaultFilter, context) => {
        return (item, result) => ({
            ...item,
            info: `ID: ${result.id}`,
        });
    }
);
```

### Classic Editor Integration

The Classic Editor entry point (`classic-editor.tsx`):

1. Finds all `[data-content-connect]` containers
2. Mounts `RelationshipManager` components into each
3. Intercepts form submission to call `persistContentConnectChanges()`
4. Re-submits form after persistence completes

## Query Integration

### WP_Query relationship_query

Query posts by their relationships using the `relationship_query` parameter.

**Basic Usage:**

```php
$query = new WP_Query([
    'post_type' => 'post',
    'relationship_query' => [
        [
            'name' => 'related_articles',
            'related_to_post' => 123,
        ],
    ],
]);
```

**Multiple Conditions (AND):**

```php
$query = new WP_Query([
    'post_type' => 'post',
    'relationship_query' => [
        'relation' => 'AND',
        [
            'name' => 'related_articles',
            'related_to_post' => 123,
        ],
        [
            'name' => 'featured_in',
            'related_to_post' => 456,
        ],
    ],
]);
```

**Order by Relationship:**

```php
$query = new WP_Query([
    'post_type' => 'post',
    'orderby' => 'relationship',
    'relationship_query' => [
        [
            'name' => 'related_articles',
            'related_to_post' => 123,
        ],
    ],
]);
```

**Related to User:**

```php
$query = new WP_Query([
    'post_type' => 'post',
    'relationship_query' => [
        [
            'name' => 'authored_by',
            'related_to_user' => 1,
        ],
    ],
]);
```

### WP_User_Query relationship_query

Query users by their relationships.

```php
$query = new WP_User_Query([
    'relationship_query' => [
        [
            'name' => 'editors',
            'related_to_post' => 123,
        ],
    ],
]);
```

## Developer API

### Defining Relationships

Hook into `tenup-content-connect-init` to define relationships:

```php
add_action('tenup-content-connect-init', function() {
    $registry = \TenUp\ContentConnect\Helpers\get_registry();

    // Post-to-Post relationship
    $registry->define_post_to_post(
        'post',           // From post type
        'post',           // To post type (can be array)
        'related',        // Relationship name
        [
            'enable_from_ui' => true,
            'enable_to_ui' => true,
            'from_labels' => [
                'name' => 'Related Posts',
                'singular' => 'Related Post',
            ],
            'to_labels' => [
                'name' => 'Related Posts',
                'singular' => 'Related Post',
            ],
            'from_sortable' => true,
            'to_sortable' => true,
            'from_max_items' => 0,  // 0 = unlimited
            'to_max_items' => 5,
        ]
    );

    // Post-to-User relationship
    $registry->define_post_to_user(
        'post',           // Post type
        'authors',        // Relationship name
        [
            'enable_from_ui' => true,
            'from_labels' => [
                'name' => 'Authors',
                'singular' => 'Author',
            ],
            'from_sortable' => true,
        ]
    );
});
```

### Programmatic Relationship Management

```php
use function TenUp\ContentConnect\Helpers\get_registry;

$registry = get_registry();

// Get relationship object
$relationship = $registry->get_post_to_post_relationship('post', 'post', 'related');

// Add a relationship
$relationship->add_relationship($post_id_1, $post_id_2);

// Remove a relationship
$relationship->delete_relationship($post_id_1, $post_id_2);

// Get related IDs
$related_ids = $relationship->get_related_object_ids($post_id);

// Replace all relationships
$relationship->replace_relationships($post_id, [10, 20, 30]);

// Save sort order
$relationship->save_sort_data($post_id, [30, 10, 20]);
```

### Using Helper Functions

```php
use function TenUp\ContentConnect\Helpers\get_post_relationships_data;
use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_by;

// Get all relationships for a post (metadata only)
$relationships = get_post_relationships_data($post_id);

// Get relationships with related entities included
$relationships = get_post_relationships_data($post_id, 'any', false, 'embed');

// Get all post-to-post relationships for a post type
$relationships = get_post_to_post_relationships_by('post_type', 'post');
```

### Available PHP Filters

| Filter | Description |
|--------|-------------|
| `tenup_content_connect_post_relationship_data` | Modify relationship data |
| `tenup_content_connect_post_ui_query_args` | Filter post query args |
| `tenup_content_connect_post_ui_user_query_args` | Filter user query args |
| `tenup_content_connect_search_posts_query_args` | Filter search query for posts |
| `tenup_content_connect_search_users_query_args` | Filter search query for users |
| `tenup_content_connect_final_post` | Modify post data before output |
| `tenup_content_connect_final_user` | Modify user data before output |
| `tenup_content_connect_post_item_data` | Filter post item in REST/helpers |
| `tenup_content_connect_user_item_data` | Filter user item in REST/helpers |

### Available PHP Actions

| Action | Description |
|--------|-------------|
| `tenup-content-connect-init` | Define relationships (fires at priority 100 on init) |

### Available JavaScript Filters

| Filter | Description |
|--------|-------------|
| `contentConnect.searchResultFilter` | Customize search result display |
| `contentConnect.pickedItemFilter` | Customize picked item display |
| `contentConnect.pickedItemPreviewComponent` | Replace preview component |

## Security Considerations

### REST API Authentication

All V2 endpoints require:

- Authenticated user
- `edit_post` capability for the target post

```php
'permission_callback' => function($request) {
    $post_id = $request->get_param('id');
    return current_user_can('edit_post', $post_id);
}
```

### SQL Escaping

All database operations use `$wpdb->prepare()` for parameterized queries:

```php
$wpdb->prepare(
    "SELECT id2 FROM {$table} WHERE id1 = %d AND name = %s",
    $post_id,
    $this->name
);
```

## Build System

### PHP Dependencies (Composer)

```json
{
    "require": {
        "php": ">=7.4",
        "composer/installers": "^2.3"
    },
    "require-dev": {
        "10up/phpcs-composer": "^3.0",
        "phpcompatibility/php-compatibility": "dev-develop as 9.99.99",
        "phpunit/phpunit": "^9.0",
        "yoast/phpunit-polyfills": "^4.0"
    },
    "autoload": {
        "psr-4": {
            "TenUp\\ContentConnect\\": "includes",
            "TenUp\\ContentConnect\\Tests\\": "tests/php"
        },
        "files": [
            "includes/Helpers.php"
        ]
    }
}
```

**Note:** Helper functions are loaded via Composer's `files` autoload to ensure they are available globally.

### JavaScript Dependencies (npm)

```json
{
    "dependencies": {
        "@10up/block-components": "^1.22.1",
        "10up-toolkit": "^6.5.1"
    },
    "devDependencies": {
        "@wordpress/api-fetch": "^7.18.0",
        "@wordpress/data": "^10.18.0",
        "@wordpress/edit-post": "^8.18.0",
        "@wordpress/editor": "^14.18.0",
        "@wordpress/env": "^10.39.0",
        "@wordpress/html-entities": "^4.18.0",
        "@wordpress/url": "^4.18.0",
        "core-js": "^3.38.0",
        "node-wp-i18n": "^1.2.7"
    }
}
```

### Build Commands

```bash
# Development (watch mode)
npm run start

# Production build
npm run build

# Lint JavaScript
npm run lint-js

# Format JavaScript
npm run format-js

# Run PHPUnit tests
npm run test:unit
```

Build outputs:

- `dist/js/block-editor.js`
- `dist/js/block-editor.asset.php`
- `dist/js/classic-editor.js`
- `dist/js/classic-editor.asset.php`

## Testing

### PHPUnit Tests

```bash
# Start wp-env
npm run wp-env:start

# Run tests
npm run test:unit

# Or directly
./vendor/bin/phpunit -c phpunit.xml
```

### Test Structure

```
tests/php/
├── Helpers/           # Helper function tests
│   ├── GetPluginTest.php
│   ├── GetRegistryTest.php
│   ├── GetRelatedIdsByNameTest.php
│   ├── GetPostToPostRelationshipsByTest.php
│   ├── GetPostToUserRelationshipsByTest.php
│   ├── GetPostRelationshipsDataTest.php
│   ├── GetPostToPostRelationshipsDataTest.php
│   └── GetPostToUserRelationshipsDataTest.php
└── API/V2/           # REST API tests
    ├── RelationshipsRouteTest.php
    ├── PostRelationshipsRouteTest.php
    └── RelatedEntitiesRouteTest.php
```

## Version History

- **2.0.0** - React migration, REST API V2, WordPress Data Store
- **1.6.0** - Previous version (Vue.js UI)
- Schema version: 0.1.10

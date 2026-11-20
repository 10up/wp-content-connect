export type ContentConnectRelatedEntity = {
	// The REST API returns a numeric ID, but the ContentPicker component and
	// persistence layer may surface it as a string, so allow both.
	id: number | string;
	name: string;
	// Object type of the entity (post type slug, or 'user'). Required by the
	// 10up ContentPicker component.
	type: string;
	// Unique identifier used by the 10up ContentPicker component.
	uuid: string;
};

// This is the shape the response from the `/content-connect/v2/post/${postId}/related?rel_key=${relKey}` endpoint returns
export type ContentConnectRelatedEntities = ContentConnectRelatedEntity[];

export type ContentConnectRelationshipLabels = {
	name: string;
};

export type ContentConnectRelationship = {
	rel_key: string;
	rel_type: 'post-to-post' | 'post-to-user';
	rel_name: string;
	object_type: 'post' | 'user';
	post_type: string[];
	labels: ContentConnectRelationshipLabels;
	sortable: boolean;
	current_post_id: number;
	max_items: number;
	enable_ui: boolean;
};

// This is the shape the response from the `/content-connect/v2/post/${postId}/relationships` endpoint returns
export type ContentConnectRelationships = {
	[key: string]: ContentConnectRelationship;
};

// used as the JSON body for the POST request to update relationships
export type ContentConnectUpdateRelationshipsBody = {
	related_ids: number[];
};

export type ContentConnectState = {
	relationships: {
		[key: string]: ContentConnectRelationships;
	};
	relatedEntities: {
		[key: string]: ContentConnectRelatedEntities;
	};
	dirtyEntityIds: Set<number>;
};

export type Term = {
	count: number;
	description: string;
	id: number;
	link: string;
	meta: Record<string, unknown>;
	name: string;
	parent: number;
	slug: string;
	taxonomy: string;
};

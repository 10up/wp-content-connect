export type ContentConnectRelatedEntity = {
	id: number;
	name: string;
};

// This is the shape the response from the `/content-connect/v2/post/${postId}/related?rel_key=${relKey}` endpoint returns
export type ContentConnectRelatedEntities = ContentConnectRelatedEntity[];

export type ContentConnectRelationshipLabels = {
	name: string;
};

type ContentConnectRelationshipBase = {
	rel_key: string;
	rel_name: string;
	labels: ContentConnectRelationshipLabels;
	sortable: boolean;
	current_post_id: number;
	max_items: number;
	enable_ui: boolean;
};

export type ContentConnectPostToPostRelationship = ContentConnectRelationshipBase & {
	rel_type: 'post-to-post';
	object_type: 'post';
	post_type: string[];
};

export type ContentConnectPostToUserRelationship = ContentConnectRelationshipBase & {
	rel_type: 'post-to-user';
	object_type: 'user';
};

export type ContentConnectRelationship =
	| ContentConnectPostToPostRelationship
	| ContentConnectPostToUserRelationship;

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
		[postId: number]: ContentConnectRelationships;
	};
	relatedEntities: {
		[key: string]: ContentConnectRelatedEntities;
	};
	dirtyEntityIds: Set<number>;
};

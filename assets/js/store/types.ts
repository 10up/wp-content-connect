export type ContentConnectRelatedEntity = {
	id: number;
	name: string;
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
		[postId: number]: ContentConnectRelationships;
	};
	relatedEntities: {
		[key: string]: ContentConnectRelatedEntities;
	};
	dirtyEntityIds: Set<number>;
};

export type RelType = 'post-to-post' | 'post-to-user';

// UI settings for one side of a relationship, as stored in `from_args` / `to_args`.
export type SideArgs = {
	enable_ui: boolean;
	sortable: boolean;
	max_items: number;
	labels?: {
		name?: string;
	};
};

export type RelationshipMeta = {
	rel_type: RelType;
	rel_from: string;
	rel_to: string[];
	rel_name: string;
	from_args: SideArgs;
	to_args: SideArgs;
};

// A `cc_relationship` record from `/content-connect/v2/custom-relationships` in the edit context.
export type CustomRelationshipRecord = {
	id: number;
	status: string;
	title: { raw: string; rendered?: string } | string;
	meta: RelationshipMeta;
	rel_key: string;
	registration_error: string | null;
};

// A relationship registered from code, from `/content-connect/v2/relationships`.
export type CodeRelationship = {
	rel_key: string;
	rel_type: RelType;
	rel_name: string;
	source: 'code' | 'custom';
	post_id: number | null;
	post_type?: string;
	labels?: { name?: string };
	sortable?: boolean;
	enable_ui?: boolean;
	max_items?: number;
	from?: {
		object_type: string;
		labels: { name?: string };
		max_items: number;
		sortable: boolean;
		enable_ui: boolean;
	};
	to?: {
		object_types: string[];
		labels: { name?: string };
		max_items: number;
		sortable: boolean;
		enable_ui: boolean;
	};
};

export type RowStatus = 'active' | 'disabled' | 'error';

// One row in the relationships list, from either source.
export type Row = {
	id: string;
	source: 'code' | 'custom';
	postId?: number;
	label: string;
	relType: RelType;
	relKey: string;
	relName: string;
	from: string;
	to: string[];
	fromArgs: SideArgs;
	toArgs: SideArgs;
	status: RowStatus;
	error?: string | null;
	bidirectional: boolean;
};

// Flat values edited by the relationship form.
export type FormValues = {
	title: string;
	rel_type: RelType;
	rel_from: string;
	rel_to: string[];
	rel_name: string;
	from_enable_ui: boolean;
	from_label: string;
	from_sortable: boolean;
	from_max_items: number;
	to_enable_ui: boolean;
	to_label: string;
	to_sortable: boolean;
	to_max_items: number;
};

export type PostTypeElement = {
	value: string;
	label: string;
};

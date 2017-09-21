<template>
	<div class="content-connect-picker-list-container">
		<ul class="content-connect-picker">
			<draggable v-if="sortable === true" v-model="localItems" :options="{ghostClass: 'ghost'}" @start="drag=true" @end="drag=false">
				<li v-for="item in items" class="content-connect-picker-list-item sortable">
					<span class="content-connect-grab-icon dashicons dashicons-move"></span>
					<span class="content-connect-selected-item-name">{{ item.name }}</span>
					<span class="delete-item content-connect-delete-button" v-on:click.prevent="deleteItem(item)">delete</span>
				</li>
			</draggable>

			<li v-if="sortable === false" v-for="item in items" class="content-connect-picker-list-item">
				<span class="content-connect-selected-item-name">{{ item.name }}</span>
				<span class="delete-item content-connect-delete-button" v-on:click.prevent="deleteItem(item)">delete</span>
			</li>
		</ul>
	</div>
</template>

<style scoped>
	* {
		box-sizing: border-box;
	}

	.content-connect-picker-list-item {
		width: 100%;
		position: relative;
		padding: 1em 1em 1em 0.5em;
	}

	.content-connect-picker-list-item.sortable {
		cursor: move;
	}

	.content-connect-picker-list-item:nth-child(odd) {
		background-color: #f9f9f9;
	}

	.content-connect-picker-list-item.ghost {
		opacity: 0.5;
		background: #c8ebfb;
	}

	.content-connect-grab-icon {
		font-size: 16px;
		color: #bbb;
	}

	.content-connect-delete-button {
		color: #a00;
		visibility: hidden;
		display: inline-block;
		float: right;
		position: relative;
		cursor: pointer;
	}

	.content-connect-delete-button:hover {
		color: #dc3232;
	}

	.content-connect-picker-list-item:hover .content-connect-delete-button {
		visibility: visible;
	}
</style>

<script>
	var draggable = require( 'vuedraggable' );

	export default {
		props: {
			items: {},
			sortable: {},
		},
		components: {
			draggable
		},
		computed: {
			localItems: {
				get() {
					return this.items;
				},
				set( items ) {
					this.$emit( 'reorder-items', items );
				}
			}
		},
		methods: {
			deleteItem( item ) {
				this.$emit( 'delete-item', item );
			}
		}
	}
</script>

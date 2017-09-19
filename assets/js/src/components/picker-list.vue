<template>
	<div class="p2p-picker-list-container">
		<ul class="p2p-picker">
			<draggable v-model="localItems" :options="{ghostClass: 'ghost'}" @start="drag=true" @end="drag=false">
				<li v-for="item in items" class="p2p-picker-list-item">
					<span class="p2p-grab-icon dashicons dashicons-move"></span>
					<span class="p2p-selected-item-name">{{ item.name }}</span>
					<span class="delete-item p2p-delete-button" v-on:click.prevent="deleteItem(item)">delete</span>
				</li>
			</draggable>
		</ul>
	</div>
</template>

<style scoped>
	* {
		box-sizing: border-box;
	}

	.p2p-picker-list-item {
		width: 100%;
		position: relative;
		padding: 1em 1em 1em 0.5em;
		cursor: move;
	}

	.p2p-picker-list-item:nth-child(odd) {
		background-color: #f9f9f9;
	}

	.p2p-picker-list-item.ghost {
		opacity: 0.5;
		background: #c8ebfb;
	}

	.p2p-grab-icon {
		font-size: 16px;
		color: #bbb;
	}

	.p2p-delete-button {
		color: #a00;
		visibility: hidden;
		display: inline-block;
		float: right;
		position: relative;
		cursor: pointer;
	}

	.p2p-delete-button:hover {
		color: #dc3232;
	}

	.p2p-picker-list-item:hover .p2p-delete-button {
		visibility: visible;
	}
</style>

<script>
	var draggable = require( 'vuedraggable' );

	export default {
		props: {
			items: {}
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

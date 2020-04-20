<template>
	<div class="content-connect-picker-list-container">
		<ul class="content-connect-picker">
			<draggable v-if="sortable === true" v-model="localItems" :options="{ghostClass: 'ghost'}" @start="drag=true" @end="drag=false">
				<li v-for="item in paginatedItems" class="content-connect-picker-list-item sortable">
					<span class="content-connect-grab-icon dashicons dashicons-move"></span>
					<span class="content-connect-selected-item-name">{{ item.name }}</span>
					<span class="delete-item content-connect-delete-button" v-on:click.prevent="deleteItem(item)">delete</span>
				</li>
			</draggable>

			<li v-if="sortable === false" v-for="item in paginatedItems" class="content-connect-picker-list-item">
				<span class="content-connect-selected-item-name">{{ item.name }}</span>
				<span class="delete-item content-connect-delete-button" v-on:click.prevent="deleteItem(item)">delete</span>
			</li>
		</ul>

        <div class="content-connect-picker-pagination" v-if="paginated">
			<a class="prev-page" v-if="!isFirstPage" v-on:click.prevent.stop="prevPage()">‹ Previous Page</a>
			<a class="next-page" v-if="hasNextPage" v-on:click.prevent.stop="nextPage()">Next Page ›</a>
		</div>

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

    .content-connect-picker-pagination {
		height: 3em;
		border-top: 1px solid #ddd;
		padding-top: 20px;
	}

	.content-connect-picker-pagination a {
		cursor: pointer;
	}

	.content-connect-picker-pagination .next-page {
		float: right;
	}

</style>

<script>
	var draggable = require( 'vuedraggable' );

    export const pickerListPageSize = 10;
	export default {
		props: {
			items: {},
            sortable: {}
        },
        
		components: {
            draggable
        },
        
        data() {
            return {
                state: {
                    currentPage: 0
                }
            }
        },
        
        watch: {
            items: function( newItems ) {
                this.state.currentPage = 0;
            }
        },

		computed: {
			localItems: {
				get() {
					return this.items ? this.items : [];
				},
				set( items ) {
					this.$emit( 'reorder-items', items );
				}
            },

            itemCount() {
                return (this.items ? this.items : []).length;
            },

            paginatedItems() {
                return this.items ? this.items.slice( 
                        this.state.currentPage * pickerListPageSize,  
                        Math.min(this.items.length, (this.state.currentPage * pickerListPageSize) + pickerListPageSize) )
                    : [];
            },
            
            paginated: {
                get() {
                    return this.itemCount > pickerListPageSize;
                }
            },

            maxPage() {                
                console.log(this.items);
                return parseInt(this.itemCount / 10) + (this.itemCount % 10 > 0 ? 1 : 0) - 1;
            },

            hasNextPage() {
                return this.state.currentPage < this.maxPage;            
            },

            isFirstPage() {
                return this.state.currentPage <= 0;
            }
        },
        
		methods: {
			deleteItem( item ) {
				this.$emit( 'delete-item', item );

                if ( this.maxPage < this.state.currentPage )
                {
                    this.prevPage();
                }
            },

            nextPage() {
                this.state.currentPage++;
            },
            
			prevPage() {
				this.state.currentPage--;
            },
        }
	}
</script>

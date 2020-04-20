<template>
	<div class="content-connect-picker-search-container">
		<label class="content-connect-picker-search-input-label" :for="_uid">Search</label>
		<div class="content-connect-picker-search-input-container">
			<form @submit.prevent="search">
				<input class="content-connect-picker-search-input widefat" type="text" :id="_uid" v-model="searchtext">
				<button class="button" type="submit">Search</button>
			</form>

		</div>

		<ul class="content-connect-picker-search-list">
			<li v-for="result in results" class="content-connect-picker-search-item result">
				<span class="content-connect-selected-item-name">{{ result.name }}</span>
				<span v-if="!result.added" class="add-item content-connect-add-button" v-on:click.prevent.stop="add(result)">add</span>
				<span v-if="result.added" class="add-item content-connect-already-added" >Added</span>
			</li>
			<li class="content-connect-picker-search-item searching" v-if="searching">
				<p>
					<span class="spinner is-active"></span>
					Searching...
				</p>
			</li>
			<li class="content-connect-picker-search-item error" v-if="! searching && searcherror.length > 0">
				<p class="error">{{ searcherror }}</p>
			</li>
		</ul>

		<div class="content-connect-picker-pagination" v-if="! searching && ( morePages || prevPages )">
			<a class="prev-page" v-if="prevPages" v-on:click.prevent.stop="prevPage()">‹ Previous Page</a>
			<a class="next-page" v-if="morePages" v-on:click.prevent.stop="nextPage()">Next Page ›</a>
		</div>
	</div>
</template>

<style scoped>
	* {
		box-sizing: border-box;
	}

	.content-connect-picker-search-container {
		padding-bottom: 20px;
        padding-top: 20px;
	}

	.content-connect-picker-search-input-label {
		display: block;
	}

	.content-connect-picker-search-input-container form {
		display: flex;
	}

	.content-connect-picker-search-input {
		flex: 1;
		margin-right: 0.5em;
	}

	.content-connect-picker-search-item {
		width: 100%;
		position: relative;
		padding: 1em 1em 1em 0.5em;
	}

	.content-connect-picker-search-item.result:nth-child(odd) {
		background-color: #f9f9f9;
	}

	.content-connect-picker-search-item.searching .spinner {
		float: left;
		margin-top: 0;
	}

	.content-connect-already-added,
	.content-connect-add-button {
		display: inline-block;
		float: right;
		position: relative;
	}

	.content-connect-add-button {
		color: #0073aa;
		cursor: pointer;
	}

	.content-connect-already-added {
		color: #aaa;
		font-style: italic;
	}

	.content-connect-add-button:hover {
		color: #00a0d2;
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
	export default {
		props: {
			results: {},
			searching: false,
			searcherror: "",
			prevPages: false,
			morePages: false
		},
		data: function() {
			return {
				searchtext: ''
			}
		},
		methods: {
			search() {
				this.$emit( 'search', this.searchtext );
			},
			add( item ) {
				this.$emit( 'add-item', item );
			},
			nextPage() {
				this.$emit( 'next-page' );
			},
			prevPage() {
				this.$emit( 'prev-page' );
			}
		}
	}
</script>

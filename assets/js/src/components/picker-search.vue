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
			<li v-for="result in results" class="content-connect-picker-search-item">
				<span class="content-connect-selected-item-name">{{ result.name }}</span>
				<span class="add-item content-connect-add-button" v-on:click.prevent.stop="add(result)">add</span>
			</li>
			<li class="content-connect-picker-search-searching" v-if="searching">
				<span class="spinner is-active"></span>
				Searching...
			</li>
		</ul>
	</div>
</template>

<style scoped>
	* {
		box-sizing: border-box;
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

	.content-connect-picker-search-item,
	.content-connect-picker-search-searching {
		width: 100%;
		position: relative;
		padding: 1em 1em 1em 0.5em;
	}

	.content-connect-picker-search-item:nth-child(odd) {
		background-color: #f9f9f9;
	}

	.content-connect-picker-search-searching .spinner {
		float: left;
		margin-top: 0;
	}

	.content-connect-add-button {
		color: #0073aa;
		display: inline-block;
		float: right;
		position: relative;
		cursor: pointer;
	}

	.content-connect-add-button:hover {
		color: #00a0d2;
	}

</style>

<script>
	export default {
		props: {
			results: {},
			searching: false,
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
			}
		}
	}
</script>

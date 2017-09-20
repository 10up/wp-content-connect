<template>
	<div class="p2p-picker-search-container">
		<label class="p2p-picker-search-input-label" :for="_uid">Search</label>
		<div class="p2p-picker-search-input-container">
			<form @submit.prevent="search">
				<input class="p2p-picker-search-input widefat" type="text" :id="_uid" v-model="searchtext">
				<button class="button" type="submit">Search</button>
			</form>

		</div>

		<ul class="p2p-picker-search-list">
			<li v-for="result in results" class="p2p-picker-search-item">
				<span class="p2p-selected-item-name">{{ result.name }}</span>
				<span class="add-item p2p-add-button" v-on:click.prevent.stop="add(result)">add</span>
			</li>
		</ul>
	</div>
</template>

<style scoped>
	* {
		box-sizing: border-box;
	}

	.p2p-picker-search-input-label {
		display: block;
	}

	.p2p-picker-search-input-container form {
		display: flex;
	}

	.p2p-picker-search-input {
		flex: 1;
		margin-right: 0.5em;
	}

	.p2p-picker-search-item {
		width: 100%;
		position: relative;
		padding: 1em 1em 1em 0.5em;
	}

	.p2p-picker-search-item:nth-child(odd) {
		background-color: #f9f9f9;
	}

	.p2p-add-button {
		color: #0073aa;
		display: inline-block;
		float: right;
		position: relative;
		cursor: pointer;
	}

	.p2p-add-button:hover {
		color: #00a0d2;
	}

</style>

<script>
	export default {
		props: {
			results: {}
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

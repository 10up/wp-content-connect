<template>
	<div class="p2p-picker-list-container">
		<ul class="p2p-picker">
			<li v-for="item in items" class="p2p-picker-list-item">
				<span class="p2p-grab-icon dashicons dashicons-move"></span>
				<span class="p2p-selected-item-name">{{ item.name }}</span>
				<span class="delete-item p2p-delete-button">delete</span>
			</li>
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
	}

	.p2p-picker-list-item:nth-child(odd) {
		background-color: #f9f9f9;
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
	export default {
		props: {
			items: {}
		}
	}
</script>

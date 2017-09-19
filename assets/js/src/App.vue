<template>
	<div class="Post To Post vtab-frame">
		<div class="vtab-left">
			<div class="vtab-frame-menu">
				<div class="vtab-menu">
					<template v-if="relationships.length">
						<a class="vtab-menu-item"
								v-for="relationship in relationships"
								v-bind:class="activeMenuItem(relationship)"
								v-on:click.prevent="setActiveRelationship(relationship)">
							{{ relationship.labels.name }}
						</a>
					</template>
				</div>
			</div>
		</div>
		<div class="vtab-right">
			<template v-if="activeRelationship">
				<div class="vtab-frame-title">
					<h1>{{ activeRelationship.labels.name }}</h1>
				</div>

				<div class="vtab-frame-content">
					<div class="vtab-content-area">
						<picker-list :items="activeRelationship.selected"></picker-list>
						<picker-search v-on:add-item="addSearchItem"></picker-search>
					</div>
				</div>

				<!--<div class="vtab-frame-toolbar"></div>-->
			</template>
		</div>
	</div>
</template>

<style lang="sass">
	#tenup-p2p-relationships .inside {
		margin: 0;
		padding: 0;
	}

	/* Basically mimics the style of the vertical tab interface in the media modal */
	.vtab-frame {
		display: flex;
		flex-flow: row wrap;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
		font-size: 12px;

		a {
			border-bottom: none;
			color: #0073aa;
		}
	}

	.vtab-left {
		flex: 0 200px;
	}

	.vtab-right {
		flex: 1 calc(100% - 232px); // 200px for left sidebar, 32 px for (16*2) padding below
		padding: 0 16px;
	}

	.vtab-frame-menu {
		height: 100%;
	}

	.vtab-menu {
		margin: 0;
		padding: 10px 0;
		border-right-width: 1px;
		border-right-style: solid;
		border-right-color: #ccc;
		background: #f3f3f3;
		height: calc(100% - 20px); // 20px is for the padding on top and bottom

		.separator {
			height: 0;
			margin: 12px 20px;
			padding: 0;
			border-top: 1px solid #ddd;
		}

		.active,
		.active:hover {
			color: #23282d;
			font-weight: 600;
		}
	}

	.vtab-menu > a {
		display: block;
		position: relative;
		padding: 8px 20px;
		margin: 0;
		color: #0073aa;
		line-height: 18px;
		font-size: 14px;
		text-decoration: none;

		&:hover {
			color: #0073aa;
			background: rgba(0, 0, 0, .04);
		}
	}

	.vtab-frame-title {
		height: 50px;
		display: flex;
		align-items: center;

		i {
			margin-right: 0.5em;
		}

		h1 {
			padding: 0;
			font-size: 22px;
			line-height: 50px;
			margin: 0;
		}
	}

	.vtab-frame-content {
		background: #fff;
		bottom: 61px;
	}

	.vtab-content-area {

	}

	.vtab-frame-toolbar {
		border-top: 1px solid #ddd;
		height: 60px;
	}

	.vtab-grid-list {
		display: flex;
		flex-wrap: wrap;
	}

	.vtab-grid-list-item {
		margin: 10px;
		box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.1), inset 0 0 0 1px rgba(0, 0, 0, 0.1);
		background: #eee;
		cursor: pointer;
		text-align: center;
		width: 150px;
		height: 150px;
		position: relative;
	}

	.vtab-grid-list-item-icon {
		height: 120px;
		font-size: 64px;
		display: flex;
		align-items: center;
		justify-content: center;

		.dashicons {
			font-size: inherit;
			height: auto;
			width: auto;
			max-width: 80%;
			max-height: 80%;
		}
	}

	.vtab-grid-list-item-title {
		box-sizing: border-box;
		position: absolute;
		bottom: 0;
		left: 0;
		width: 100%;
		margin: 0;
		line-height: 1.2;
		padding: 8px;
		overflow: hidden;
		max-height: 100%;
		word-wrap: break-word;
		text-align: center;
		font-weight: bold;
		background: rgba(255, 255, 255, 0.8);
		box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.15);
	}

</style>

<script>
	var PickerList = require( './components/picker-list.vue' );
	var PickerSearch = require( './components/picker-search.vue' );

	module.exports = {
		data: function() {
			return Object.assign({}, {
				"activeRelationship": P2PData.relationships[0],
			}, window.P2PData);
		},
		components: {
			PickerList: PickerList,
			PickerSearch: PickerSearch
		},
		methods: {
			activeMenuItem: function( relationship ) {
				return {
					active: ( relationship === this.activeRelationship )
				};

			},
			setActiveRelationship: function( relationship ) {
				this.activeRelationship = relationship;
			},
			addSearchItem: function( item ) {
				this.activeRelationship.selected.push( item );
			}
		}
	}
</script>

var Vue = require( 'vue' );
var App = require( './App.vue' );

Vue.use( require( 'vue-resource' ) );

window.p2papp = new Vue({
	render: createEle => createEle( App )
}).$mount( '#tenup-p2p-app' );


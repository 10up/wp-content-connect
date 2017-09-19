var Vue = require( 'vue' );
var App = require( './App.vue' );

window.p2papp = new Vue({
	render: createEle => createEle( App )
}).$mount( '#tenup-p2p-app' );


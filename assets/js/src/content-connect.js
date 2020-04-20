import Vue from 'vue';
import VueResource from 'vue-resource';
import App from './App.vue';

Vue.use( VueResource );
Vue.config.devtools = true;

// Adds the global wp_rest nonce, so we can auth a user
Vue.http.interceptors.push(function(request, next) {
	request.headers.set( 'X-WP-Nonce', ContentConnectData.nonces.wp_rest );

	next();
});

window.ContentConnectApp = new Vue({
	render: createEle => createEle( App )
}).$mount( '#tenup-content-connect-app' );


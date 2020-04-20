'use strict'
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
    mode: "production",
    entry: [
        './assets/js/src/content-connect.js'
    ],
    module: {
        rules: [
        {
            test: /\.vue$/,
            loader: 'vue-loader',
        },
        {
            test: /\.css$/,
            use: [
                'vue-style-loader',
                'css-loader'
            ]
        }, 
        {
            test: /\.scss$/,
            use: [
                'vue-style-loader',
                'css-loader',
                'sass-loader'
            ]
        },
        ]
    },
    plugins: [
        new VueLoaderPlugin()
    ],
    output: {
        filename: "content-connect.js"
    },
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                test: /\.js(\?.*)?$/i,
            }),
        ],
    },
}
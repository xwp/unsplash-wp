/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const OptimizeCSSAssetsPlugin = require( 'optimize-css-assets-webpack-plugin' );
const RtlCssPlugin = require( 'rtlcss-webpack-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const WebpackBar = require( 'webpackbar' );

/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const {
	defaultRequestToExternal,
	defaultRequestToHandle,
} = require( '@wordpress/dependency-extraction-webpack-plugin/util' );

const sharedConfig = {
	output: {
		path: path.resolve( process.cwd(), 'assets', 'js' ),
		filename: '[name].js',
		chunkFilename: '[name].js',
	},
	optimization: {
		minimizer: [
			new TerserPlugin( {
				parallel: true,
				sourceMap: false,
				cache: true,
				terserOptions: {
					output: {
						comments: /translators:/i,
					},
				},
				extractComments: false,
			} ),
			new OptimizeCSSAssetsPlugin( {} ),
		],
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.css$/,
				use: [
					// prettier-ignore
					MiniCssExtractPlugin.loader,
					'css-loader',
					'postcss-loader',
				],
			},
		],
	},
	plugins: [
		...defaultConfig.plugins,
		new MiniCssExtractPlugin( {
			filename: '../css/[name]-compiled.css',
		} ),
		new RtlCssPlugin( {
			filename: '../css/[name]-compiled-rtl.css',
		} ),
	],
};

const mediaSelector = {
	...defaultConfig,
	...sharedConfig,
	entry: {
		'media-selector': [ './assets/src/media-selector/index.js' ],
	},
	plugins: [
		...sharedConfig.plugins,
		new WebpackBar( {
			name: 'Media Selector',
			color: '#36f271',
		} ),
	],
};

const wpPolyfills = {
	...defaultConfig,
	...sharedConfig,
	externals: {},
	plugins: [
		new DependencyExtractionWebpackPlugin( {
			useDefaults: false,
			requestToHandle: request => {
				switch ( request ) {
					case '@wordpress/api-fetch':
					case '@wordpress/i18n':
					case '@wordpress/polyfill':
					case '@wordpress/url':
						return undefined;

					default:
						return defaultRequestToHandle( request );
				}
			},
			requestToExternal: request => {
				switch ( request ) {
					case '@wordpress/api-fetch':
					case '@wordpress/i18n':
					case '@wordpress/polyfill':
					case '@wordpress/url':
						return undefined;

					default:
						return defaultRequestToExternal( request );
				}
			},
		} ),
		new CopyWebpackPlugin( [
			{
				from: 'node_modules/lodash/lodash.js',
				to: './vendor/lodash.js',
			},
		] ),
		new WebpackBar( {
			name: 'WordPress Polyfills',
			color: '#21a0d0',
		} ),
	],
	entry: {
		'wp-api-fetch': './assets/src/polyfills/wp-api-fetch.js',
		'wp-i18n': './assets/src/polyfills/wp-i18n.js',
		'wp-polyfill': './assets/src/polyfills/wp-polyfill.js',
		'wp-url': './assets/src/polyfills/wp-url.js',
	},
};

module.exports = [ mediaSelector, wpPolyfills ];

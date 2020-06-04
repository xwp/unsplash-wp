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
const { escapeRegExp } = require( 'lodash' );
const { sep } = require( 'path' );

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

// These packages need to be bundled and not extracted to `wp.*`.
const PACKAGES_TO_BUNDLE = [
	'@wordpress/block-library/build/image/constants',
	'@wordpress/block-library/build/image/image-size',
	'@wordpress/block-library/build/image/save',
	'@wordpress/block-library/build/image/utils',
];

const blockEditor = {
	...defaultConfig,
	...sharedConfig,
	entry: {
		'block-editor': [ './assets/src/block-editor/index.js' ],
	},
	plugins: [
		...sharedConfig.plugins.filter(
			// Remove the `DependencyExtractionWebpackPlugin` if it already exists.
			plugin => ! ( plugin instanceof DependencyExtractionWebpackPlugin )
		),
		new CopyWebpackPlugin( [
			{
				from: './assets/src/block-editor/blocks/*/block.json',
				test: new RegExp( `([\\w-]+)${ escapeRegExp( sep ) }block\\.json$` ),
				to: './blocks/[1]/block.json',
			},
		] ),
		new DependencyExtractionWebpackPlugin( {
			useDefaults: false,
			requestToExternal( request ) {
				if ( PACKAGES_TO_BUNDLE.includes( request ) ) {
					return undefined;
				}

				return defaultRequestToExternal( request );
			},
			requestToHandle( request ) {
				if ( PACKAGES_TO_BUNDLE.includes( request ) ) {
					return 'wp-block-editor'; // Return block-editor as a dep.
				}

				return defaultRequestToHandle( request );
			},
		} ),
		new WebpackBar( {
			name: 'Block Editor',
			color: '#f27136',
		} ),
	],
};

const mediaSelector = {
	...defaultConfig,
	...sharedConfig,
	entry: {
		'media-selector': [ './assets/src/media-selector/index.js' ],
		'featured-image-selector': [
			'./assets/src/media-selector/featured-image-selector.js',
		],
	},
	plugins: [
		...sharedConfig.plugins,
		new WebpackBar( {
			name: 'Media Selector',
			color: '#36f271',
		} ),
	],
};

const admin = {
	...defaultConfig,
	...sharedConfig,
	entry: {
		admin: [ './assets/src/admin/index.js' ],
	},
	plugins: [
		...sharedConfig.plugins,
		new WebpackBar( {
			name: 'Admin',
			color: '#570576',
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
		'wp-i18n': './assets/src/polyfills/wp-i18n.js',
		'wp-polyfill': './assets/src/polyfills/wp-polyfill.js',
		'wp-url': './assets/src/polyfills/wp-url.js',
	},
};

module.exports = [ blockEditor, mediaSelector, wpPolyfills, admin ];

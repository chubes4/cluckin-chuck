const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'map-display/index': path.resolve( __dirname, 'src/map-display/index.js' ),
		'map-display/frontend': path.resolve( __dirname, 'src/map-display/frontend.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};

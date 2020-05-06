module.exports = {
	verbose: true,
	testMatch: [ '**/?(*.)+(spec|test).[jt]s?(x)' ],
	preset: '@wordpress/jest-preset-default',
	collectCoverageFrom: [ 'assets/src/**/*.js' ],
	testPathIgnorePatterns: [
		'/bin/',
		'/build',
		'/node_modules/',
		'/tests/e2e/',
		'/vendor/',
	],
};

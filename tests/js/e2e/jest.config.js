module.exports = {
	...require( '@wordpress/scripts/config/jest-e2e.config' ),
	transformIgnorePatterns: [
		'node_modules',
	],
	setupFilesAfterEnv: [
		'<rootDir>/config/bootstrap.js',
		'@wordpress/jest-puppeteer-axe',
		'expect-puppeteer',
	],
	testPathIgnorePatterns: [
		'.git',
		'node_modules',
	],
	reporters: [ [ 'jest-silent-reporter', { useDots: true } ] ],
};

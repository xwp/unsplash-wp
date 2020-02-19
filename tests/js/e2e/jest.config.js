module.exports = {
	...require('@wordpress/scripts/config/jest-e2e.config'),
	transform: {
		'^.+\\.[jt]sx?$':
			'<rootDir>/../../../node_modules/@wordpress/scripts/config/babel-transform',
	},
	transformIgnorePatterns: ['node_modules'],
	setupFilesAfterEnv: [
		'<rootDir>/config/bootstrap.js',
		'@wordpress/jest-puppeteer-axe',
		'expect-puppeteer',
	],
	testPathIgnorePatterns: [
		'<rootDir>/.git',
		'<rootDir>/node_modules',
		'<rootDir>/dist',
	],
	reporters: [['jest-silent-reporter', { useDots: true }]],
};

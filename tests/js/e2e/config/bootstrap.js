/**
 * External dependencies
 */
import { get } from 'lodash';

/**
 * WordPress dependencies
 */
import {
	clearLocalStorage,
	enablePageDialogAccept,
	setBrowserViewport,
} from '@wordpress/e2e-test-utils';

/**
 * Environment variables
 */
const { PUPPETEER_TIMEOUT } = process.env;

/**
 * Set of console logging types observed to protect against unexpected yet
 * handled (i.e. not catastrophic) errors or warnings. Each key corresponds
 * to the Puppeteer ConsoleMessage type, its value the corresponding function
 * on the console global object.
 *
 * @type {Object<string,string>}
 */
const OBSERVED_CONSOLE_MESSAGE_TYPES = {
	warning: 'warn',
	error: 'error',
};

/**
 * Array of page event tuples of [ eventName, handler ].
 *
 * @type {Array}
 */
const pageEvents = [];

// The Jest timeout is increased because these tests are a bit slow
jest.setTimeout( PUPPETEER_TIMEOUT || 100000 );

/**
 * Adds an event listener to the page to handle additions of page event
 * handlers, to assure that they are removed at test teardown.
 */
function capturePageEventsForTearDown() {
	page.on( 'newListener', ( eventName, listener ) => {
		pageEvents.push( [ eventName, listener ] );
	} );
}

/**
 * Removes all bound page event handlers.
 */
function removePageEvents() {
	pageEvents.forEach( ( [ eventName, handler ] ) => {
		page.removeListener( eventName, handler );
	} );
}

/**
 * Adds a page event handler to emit uncaught exception to process if one of
 * the observed console logging types is encountered.
 */
function observeConsoleLogging() {
	page.on( 'console', ( message ) => {
		const type = message.type();
		if ( ! OBSERVED_CONSOLE_MESSAGE_TYPES.hasOwnProperty( type ) ) {
			return;
		}

		let text = message.text();

		// An exception is made for _blanket_ deprecation warnings: Those
		// which log regardless of whether a deprecated feature is in use.
		if ( text.includes( 'This is a global warning' ) ) {
			return;
		}

		// Viewing posts on the front end can result in this error, which
		// has nothing to do with Gutenberg.
		if ( text.includes( 'net::ERR_UNKNOWN_URL_SCHEME' ) ) {
			return;
		}

		// A bug present in WordPress 5.2 will produce console warnings when
		// loading the Dashicons font. These can be safely ignored, as they do
		// not otherwise regress on application behavior. This logic should be
		// removed once the associated ticket has been closed.
		//
		// See: https://core.trac.wordpress.org/ticket/47183
		if (
			text.startsWith( 'Failed to decode downloaded font:' ) ||
			text.startsWith( 'OTS parsing error:' )
		) {
			return;
		}

		const logFunction = OBSERVED_CONSOLE_MESSAGE_TYPES[ type ];

		// As of Puppeteer 1.6.1, `message.text()` wrongly returns an object of
		// type JSHandle for error logging, instead of the expected string.
		//
		// See: https://github.com/GoogleChrome/puppeteer/issues/3397
		//
		// The recommendation there to asynchronously resolve the error value
		// upon a console event may be prone to a race condition with the test
		// completion, leaving a possibility of an error not being surfaced
		// correctly. Instead, the logic here synchronously inspects the
		// internal object shape of the JSHandle to find the error text. If it
		// cannot be found, the default text value is used instead.
		text = get( message.args(), [ 0, '_remoteObject', 'description' ], text );

		// Disable reason: We intentionally bubble up the console message
		// which, unless the test explicitly anticipates the logging via
		// @wordpress/jest-console matchers, will cause the intended test
		// failure.

		// eslint-disable-next-line no-console
		console[ logFunction ]( text );
	} );
}

/**
 * Runs Axe tests when the block editor is found on the current page.
 *
 * @return {?Promise} Promise resolving once Axe texts are finished.
 */
async function runAxeTests() {

	await expect( page ).toPassAxeTests( {
		/**
		 * Rules are disabled, as there are still accessibility issues within gutenberg.
		 *
		 * See: https://github.com/WordPress/gutenberg/pull/15018 & https://github.com/WordPress/gutenberg/issues/15452
		 */
		disabledRules: [
			'aria-allowed-role',
			'aria-valid-attr-value',
			'button-name',
			'color-contrast',
			'dlitem',
			'duplicate-id',
			'label',
			'link-name',
			'listitem',
			'region',
		],
		exclude: [
			// Ignores elements created by metaboxes.
			'.edit-post-layout__metaboxes',
			// Ignores elements created by TinyMCE.
			'.mce-container',
		],
	} );
}

/**
 * Before every test suite run, delete all content created by the test. This ensures
 * other posts/comments/etc. aren't dirtying tests and tests don't depend on
 * each other's side-effects.
 */
// eslint-disable-next-line jest/require-top-level-describe
beforeAll( async () => {
	capturePageEventsForTearDown();
	enablePageDialogAccept();
	observeConsoleLogging();
	await setBrowserViewport( 'large' );
	await page.setDefaultNavigationTimeout( 10000 );
	await page.setDefaultTimeout( 3000 );
} );

// eslint-disable-next-line jest/require-top-level-describe
afterEach( async () => {
	await clearLocalStorage();
	//await runAxeTests();
	await setBrowserViewport( 'large' );
} );

// eslint-disable-next-line jest/require-top-level-describe
afterAll( () => {
	removePageEvents();
} );

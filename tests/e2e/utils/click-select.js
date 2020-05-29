/**
 * Clicks a based on a selector.
 *
 * This is almost a copy of the upstream util, however, it uses page.evaluate for clicking since it seems to work more reliably.
 *
 * @param {string} buttonText The text that appears on the button to click.
 */
export async function clickSelector( btnSelector ) {
	await page.waitForSelector( btnSelector );
	await page.evaluate( selector => {
		document.querySelector( selector ).click();
	}, btnSelector );
}

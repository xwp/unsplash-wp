import { SelectUnsplash, QueryUnsplash, checkType } from '../../../assets/src/media-views';

describe( 'media-views', () => {
  describe( 'SelectUnsplash', () => {
	it( 'should ...', () => {
	  // @todo add test.
	} );
  } );

  describe( 'QueryUnsplash', () => {
	it( 'should ...', () => {
	  // @todo add test.
	} );
  } );

  describe( 'checkType', () => {
	it( 'should not return true', () => {
	  expect( checkType( [] ) ).toStrictEqual( false );
	} );

	it( 'should return true', () => {
	  expect( checkType( [ 'image' ] ) ).toStrictEqual( true );
	} );
  } );
} );

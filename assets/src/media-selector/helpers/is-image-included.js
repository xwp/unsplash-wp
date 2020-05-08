export default type => {
	type = Array.isArray( type ) ? type : [ type ];
	return type.includes( 'image' );
};

export default library => {
	const applicableLibraries = [
		'insert',
		'featured-image',
		'library',
		'replace-image',
	];
	return library && applicableLibraries.includes( library );
};

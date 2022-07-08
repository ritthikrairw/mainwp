const eslintConfig = {
	root: true,
	parser: '@babel/eslint-parser',
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
	settings: {
		'import/resolver': {
			webpack: {
				config: __dirname + '/webpack.config.js',
			},
		},
	},
};

module.exports = eslintConfig;

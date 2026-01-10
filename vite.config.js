import { createWpViteConfig } from 'pressbooks-build-tools';
import { resolve } from 'path';

export default createWpViteConfig({
	input: {
		'login-form': resolve(__dirname, 'assets/src/scripts/login-form.js'),
		'pressbooks-saml-sso': resolve(__dirname, 'assets/src/scripts/pressbooks-saml-sso.js'),
	},
	outDir: 'assets/dist',
});

# Simple SAML Installer

This package serves as an installer for simplesamlphp/simplesamlphp package. What it does:

- copy of config templates
- copy of metadata templates
- config setup
- copy/generation of certificates
- enabling of some modules
- adding some custom uon:authorize module

## Requirements

1) `.env` file with APP_KEY to determine environment if not *production*. See `.env.example`

2) config/metadata files etc in following structure

	/docs
	/docs/install
	/docs/install/<env>
	/docs/install/<env>/config
	/docs/install/<env>/metadata
	/docs/install/<env>/cert
	/docs/install/<env>/modules

If you do not know what files to put in these folders, it's basically copy+modify as following:

- **config** comes from `simplesamlphp/config`
- **metadata** comes from `simplesamlphp/metadata`
- **cert** has to include `*.pem` and `*.crt` files
- **modules** any modules you want to put in (like *uon:authorize*)
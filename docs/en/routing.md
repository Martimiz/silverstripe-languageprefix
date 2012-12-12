# Routing #
The PrefixModelAsController needs to replace ModelAsController and RootURLController in
the YAML routes. For that to work it needs to come early in the route hierarchy or it will render urls like /dev and /admin unaccessible. This should work in _config/routes.yml:

	---
	Name: languageprefix
	After: '#modelascontrollerroutes'
	Before: '#coreroutes'
	---
	Director:
		rules:  
			'': 'PrefixModelAsController'
			'$Prefix/$URLSegment//$Action/$ID/$OtherID': 'PrefixModelAsController' 

Unfortunately the current setup of routes.yml in both `framework` and `cms` doesn't allow this. So for the time being, existing rules for admin and other specific url's need to be added (again!) also: 

	---
	Name: languageprefix
	After: '#modelascontrollerroutes'
	---
	Director:
	  rules:
	    '': 'PrefixModelAsController'
	    '$Prefix/$URLSegment//$Action/$ID/$OtherID': 'PrefixModelAsController'
	---
	Name: lpcoreroutes
	After: '#languageprefix'
	---
	Director:
	  rules:
	    'Security//$Action/$ID/$OtherID': 'Security'
	    '$Controller//$Action/$ID/$OtherID':  '*'
	    'api/v1/live': 'VersionedRestfulServer'
	    'api/v1': 'RestfulServer'
	    'soap/v1': 'SOAPModelAccess'
	    'dev': 'DevelopmentAdmin'
	    'interactive': 'SapphireREPL'
	---
	Name: lpadminroutes
	After: '#languageprefix'
	---
	Director:
	  rules:
	    'admin': 'AdminRootController'
	    'dev/buildcache/$Action': 'RebuildStaticCacheTask'
	---
	Name: lplegacycmsroutes
	After: '#languageprefix'
	---
	Director:
	  rules:
	    'admin/cms': '->admin/pages'

This should generally work fine with any SilverStripe 3.x install. Please report problems with other (third party) modules as an issue on [GitHub](https://github.com/Martimiz/silverstripe-languageprefix/issues)
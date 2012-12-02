# Language Prefix module #
## introduction #
The LanguagePrefix extension allows you to create links with a language prefix
for multilingual websites, using one domain only, that can be parsed by the 
PrefixModelAsController. Example:

	www.mydomain.com/en/
	www.mydomain.com/nl/
 

## Installation ##
Rename the module to 'languageprefix' and copy to the root of your site. Make sure the 
[Translatable module](https://github.com/silverstripe/silverstripe-translatable) is installed and enabled on your website. Then add the following to your _config.php (enabled by default) and do a /dev/build?flush=1:

	:::php
	Object::add_extension('Page', 'LanguagePrefix'); 
	Object::add_extension('Page_Controller', 'LanguagePrefix_Controller'); 
	
`Important:` next you need to add the following bit of code to your `Page` class. This will will make sure that all pagelinks on your website will have the language prefix added to them:

	:::php
	/**
	 * Adds the prefix to all pagelinks  
	 * @param $action
	 * @return string 
	 */
	public function Link($action = null) {
		return $this->PrefixLink($action);
	} 	

## Using custom prefixes ##
By default the module uses the locale as prefix:

	www.mydomain.ext/en_US/about-us
	www.mydomain.ext/nl_NL/over-ons

To use custom language prefixes, add the following to your _config.php:

	:::php
	// Format: locale => prefix
	LanguagePrefix::$language_prefixes = array(
	    'en_US' => 'en',
	    'nl_NL' => 'nl'
	);

## Enabling BaseHref ##
Although `BaseHref` is deprecated in SilverStripe 3.x, it is still used in some templates to add a link to the homepage (Simple!). To enabl it's use, add the following bit of code to your Page_Controller:

	:::php
	/**
	 * BaseHref is deprecated, but still used in the Simple Template.
	 * This function cannot be overruled from LanguagePrefix_Controller!
	 */
	public function BaseHref() {
		return $this->HomeLink();
	}  

## HomeLink ##
To create custom links to the homepage for your current locale, you can use the following instead of $BaseHref:

	$HomeLink

## Routing issue ##
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

## More information ##
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
	LanguagePrefix::$locale_prefix_map = array(
	    'en_US' => 'en',
	    'nl_NL' => 'nl'
	);

## Redirect root
If you wish, you can redirect the root to the default locale prefix in a number of ways, for example using .htaccess or lighttpd configuration redirect rules, but the easiest is probably to use a Director rule in _config.php. Example (depending on your prefix): 

	:::php
	Director::addRules(100, array(
	    '' => '->/en/',
    ));
 
 The same rule could also be applied in _config/routes.yml. Replace:
 
 	:::php
 	Director:
	    rules:
	        '': 'PrefixModelAsController'
 
 by (and don't forget to flush):
 
 	:::
 	Director:
	    rules:
	        '': '->/en/' 	
 
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

## More information ##
# Language Prefix module #
## introduction #
The LanguagePrefix extension allows you to create links with a language prefix
for multilingual websites, using one domain only, that can be parsed by the 
PrefixModelAsController. Example:

	www.mydomain.com/en/
	www.mydomain.com/nl/
 

## Installation ##
Rename the module to 'languageprefix' and copy to the root of your site. Make sure the 
[Translatable module](https://github.com/silverstripe/silverstripe-translatable) is installed and enabled on your website. The LanguagePrefix module now uses YAML for its configuration. Extensions are enabled by default in _config/languageprefix.yml. Perform ?flush=1 to enable. 

### $PrefixLink is now deprecated ###

The default SiteTree->Link() function now supports language prefixes, so just use **$Link** in your templates, as you normally would.

Subclassing the Link() function in your Page class is no longer necessary, although PrefixLink is still supported for reasons of backwards compatibility.

`Note:` There is a known issue with the use of a **homepage pagelink** as a parameter to the ChildrenOf() or the Page(): Do not use 'en_US/'. You can use '/' or 'en_US/home/' if you wish.

## Using custom prefixes ##
By default the module uses the locale as prefix:

	www.mydomain.ext/en_US/about-us
	www.mydomain.ext/nl_NL/over-ons

To use custom language prefixes, change the prefixconfig seyttings in your _config/languageprefix.yml to look like this:

	:::php
	---
	Name: languageprefix-config
	---
	prefixconfig:
	  locale_prefix_map:
	    'en_US': en
	    'nl_NL': nl

## Redirect root ##
If you wish, you can redirect the root to the default locale prefix in a number of ways, for example using .htaccess or lighttpd configuration redirect rules, to create a 301 redirect. The easiest way is probably to use a Director rule in _config.php. Example (depending on your prefix): 

	:::php
	Director::addRules(100, array(
	    '' => '->/en/',
	));
 
 The same rule could also be applied to _config/routes.yml. Replace:
 
 	:::php
 	Director:
	    rules:
	        '': 'PrefixModelAsController'
 
 by (and don't forget to flush):
 
 	:::
 	Director:
	    rules:
	        '': '->/en/' 

`Note:` as this is probably not really a 301 redirect, I'm not sure how searchengines would respond to this - it might be better to stick with the .htaccess solution...	
 
## Enable BaseHref ##
Although `BaseHref` is deprecated in SilverStripe 3.x, it is still used in some templates to add a link to the homepage (Simple!). To enable its use, add the following to your Page_Controller:

	:::php
	/**
	 * This function supports the (deprecated) use of $BaseHref in templates
	 *
	 * @return string
	 */
	public function BaseHref() {
		return $this->BaseLinkForLocale();
	}

`Note:` You can use ** $BaseLinkForLocale ** in your templates to create a link to the homepage for the current locale.

## Enable duplicate URLSegments

The LanguagePrefix module supports the use of duplicate URLSegments for different languages as an optional feature, that is disabled by default. When enabled, it will automatically keep the existing URLSegment for new translations. You can now have:

	www.mydomain.com/en/faq
	www.mydomain.com/nl/faq

To enable this feature, set the following in _config/LanguagePrefix.yml

	enable_duplicate_urlsegments: true
	
**Note:** don't forget to ?flush=1. If the admin section was already open, you might have to perform a flush there as well!

## Language switcher

You can use the language switchers that are described [in the Translatable README file](https://github.com/silverstripe/silverstripe-translatable/blob/master/docs/en/index.md#switching-languages).
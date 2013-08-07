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
	Object::add_extension('SiteTree', 'LanguagePrefix'); 

## Create page-links in your template
To create the proper links in your template use ** $PrefixLink ** instead of ** $Link **:

	:::php
		<ul>
		<% loop $Menu(1) %>
			<li class="$LinkingMode"><a href="$PrefixLink" title="$Title.XML">$MenuTitle.XML</a></li>
		<% end_loop %>
		</ul>

However, if you want to support existing templates and continue to use $Link, you may add the following to your Page class:

	:::php
		public function Link($action = null) {
			return $this->PrefixLink($action);
		}
`Note:` There is a known issue with the use of a pagelink as a parameter to the ChildrenOf() or the Page() function. These functions don't allow the language prefix. Use URLSegment instead. When in doubt, stick to the $PrefixLink.  

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

## Redirect root
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

**Note:** as this is not really a 301 redirect, I'm not sure how searchengines would respond to this - it might be better to stick with the .htaccess solution...	
 
## Enabling BaseHref ##
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


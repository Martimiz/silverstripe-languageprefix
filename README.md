# LanguagePrefix module for SilverStripe CMS #

## Note: this is a test version

 * Supports SilverStripe 3.1.x + 
 * For SilverStripe 3.0.x use version 1.0

## Introduction ##

The Language Prefix module allows you to create links with a language prefix
for multilingual websites using [SilverStripe Translatable](https://github.com/silverstripe/silverstripe-translatable). Example:
 
	www.mydomain.com/en/
 	www.mydomain.com/nl/

## Changes in this version

 * LanguagePrefix now uses Translatable's enable_duplicate_urlsegments config setting when allowing duplicate URLSegments
 * Preview mode and Split mode are now supported in the CMS
 * RelativeLink() now includes the languageprefix. 
 * It is no longernecessary to define a Link() function in your class 
 * PrefixLink() is now deprecated. Use Link() instead
 * SiteTree::get_by_link() now works for all prefixed links, except for homepage links like '/en_US/': get_by_link() won't retrieve the homepage URLSegment for links that are not empty (or '/').
 
 * Config settings and the enabling of extensions are now handled by the YAML system (see _config/languageprefix.yml). 
 * Some other minor upgrades to SilverStripe 3.1 

## Usage

Setup and usage documentation: [docs/en/index.md](docs/en/index.md)

## Requirements ##

 * SilverStripe Framework 3.1+ and CMS 3.1+
 * SilverStripe Translatable module

## Maintainers ##

 * Martine Bloem (Martimiz)
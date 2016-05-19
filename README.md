# LanguagePrefix module for SilverStripe CMS #

 * Supports SilverStripe 3.1.x + 
 * For SilverStripe 3.0.x use version 1.0

## Introduction ##

The Language Prefix module allows you to create links with a language prefix
for multilingual websites using [SilverStripe Translatable](https://github.com/silverstripe/silverstripe-translatable). 
Example:
 
	www.mydomain.com/en/
 	www.mydomain.com/nl/

You can easily define your own prefix format. By default the locale (en_US, nl_NL, ...) is used. 
Optionally you can leave out the prefix for the default language

## Changes in this version

This is a rewrite of version 2.0. The Prefix is no longer a separate url param, but is extracted from the URLSegment instead. 
This allows for greater flexibility - you can now optionally use urls without prefix for the default language. 

 * $Prefix segment is stripped from the url rule (routes.yml)
 * PrefixModelAsController::handleRequest() now handles extracting the prefix from the url, and shifts the url and other params if need be
 * Optionally set `disable_prefix_for_default_lang` to remove the prefix from the default language

*Note: this version should be backwards compatible with version 2.0. Please create an issue if you find any problems* 

## Usage

Setup and usage documentation: [docs/en/index.md](docs/en/index.md)

## Requirements ##

 * SilverStripe Framework 3.1+ and CMS 3.1+
 * SilverStripe Translatable module

## Maintainers ##

 * Martine Bloem (martimiz at gmail dot com)
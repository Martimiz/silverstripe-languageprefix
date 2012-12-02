<?php

Object::add_extension('Page', 'LanguagePrefix'); 
Object::add_extension('Page_Controller', 'LanguagePrefix_Controller'); 

// Format: locale => prefix
LanguagePrefix::$language_prefixes = array(
    	'en_US' => 'en',
    	'nl_NL' => 'nl'
);

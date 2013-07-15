<?php

Object::add_extension('SiteTree', 'LanguagePrefix'); 

Object::useCustomClass('Translatable', 'LanguagePrefixTranslatable');


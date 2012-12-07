<?php
/**
 * The LanguagePrefix extension allows you to create links with a language prefix
 * for multilingual websites, using one domain only, that can be parsed by the 
 * PrefixModelAsController. Example:
 * 
 *     www.mydomain.com/en/
 *     www.mydomain.com/nl/
 * 
 * The LanguagePrefix_Controller is an extension to your Page_Controller, providing 
 * a HomeLink() function that you can use to link to the homepage for the current 
 * locale in your templates: $HomeLink
 * 
 * PrefixModelAsController replaces ModelAsController and RootURLController in
 * the YAML routes.
 * 
 * <h2>Configuration</h2>
 * 
 * <h3>Enable the LanguagePrefix module</h3>
 * Add this to your _config.php
 * 
 *     Object::add_extension('Page', 'LanguagePrefix'); 
 *     Object::add_extension('Page_Controller', 'LanguagePrefix_Controller'); 
 * 
 * <h3>Links</h3>
 * Add the following function to your Page class, to make sure that the correct 
 * links are created:
 * 
 * 	public function Link($action = null) {
 *		return $this->PrefixLink($action);
 *	}
 * 
 * <h3>BasHref support</h3>
 * $BaseHref is deprecated, but still exists in the Simple Template. To
 * make it work, add this to your Page_Controller:
 * 
 *	public function BaseHref() {
 *		return $this->HomeLink();
 *	} 
 */
class LanguagePrefix extends DataExtension {

	/**
	 * language prefix for translatable sites
	 * format: locale => prefix: 'en_US' => 'en'
	 * 
	 * @var array
	 */
	public static $locale_prefix_map = array();

	/**
	 * return the language prefix for the current locale. If no locale is given,
	 * the default Translatable locale is assumed. 
	 * 
	 * @param string locale
	 * @return string prefix
	 */
	public static function get_prefix($locale = null) {
		if (!$locale) $locale = Translatable::default_locale();

		if (isset(self::$locale_prefix_map[$locale])) {
			$prefix = self::$locale_prefix_map[$locale];
		} else {
			$prefix = $locale;
		}
		return $prefix;
	}

	/**
	 * return the $locale based on the language prefix. If no locale is given,
	 * the default Translatable locale is assumed.
	 * 
	 * @param string prefix
	 * @return string locale
	 */
	public static function get_locale_from_prefix($prefix = '') {
		
		// no prefix? must be root url, so set locale to default
		if (empty($prefix)){ 
			return  Translatable::default_locale();
		}
		
		// language prefixes defined? 
		if (!empty(self::$locale_prefix_map)) {
			$arrayLocale = array_flip(self::$locale_prefix_map); 
			$locale = (isset($arrayLocale[$prefix])) ?
				$arrayLocale[$prefix] : '';
		
		// no prefixes defined so we use the locale as a prefix	
		} else {
			$locale = $prefix;
		}				

		// do we have a valid locale?
		if ($locale) {
			$validLocales = Translatable::get_existing_content_languages();
			if (!array_key_exists($locale, $validLocales)) {	
				$locale = '';
			}	
		}
		// should we consider an empty locale at this point as faulty
		return $locale;
	}	
	
	
	/**
	 * add the prefix to your links.
	 * note: the action is necessary to request the full URLSegment from 
	 * RelativeLink()
	 * 
	 * @param Boolean $action
	 * @return string relative page url
	 */
	public function PrefixLink($action = null) {
		if(!Translatable::is_enabled()) {
			return parent::Link($action);
		}
		$link = $this->RelativeLink($action);
		$prefix = self::get_prefix($this->owner->Locale);
		
		return Controller::join_links(
			Director::baseURL(),
			$prefix,
			$link
		);		
	} 
	
	/**
	 * return a relative link to the page. If $action is true this will leave 
	 * the URLSegment intact for homepages, for use in form actions, that 
	 * would otherwise generate a page-not-found. 
	 * 
	 * @param boolean action
	 * @return string relative link
	 */
	public function RelativeLink($action = null) {
		if($this->owner->ParentID && SiteTree::nested_urls()) {
			$base = $this->owner->Parent()->RelativeLink($this->owner->URLSegment);
		} else {
			$base = $this->owner->URLSegment;
		}
		// Unset base for homepage URLSegments in their default language.
		// Homepages with action = true parameter need to retain their 
		// URLSegment. We can only do this if the homepage is on the root level.
		// Note: don't use RootURLController::get_homepage_link() because
		//       it will return the homepage for the Translatable current locale, 
		//       not the alternate locale used in the Translatable metatag, 
		//       and the homepage URLSegment cannot be stripped that way    
		if (
			empty($action) && !$this->owner->ParentID &&
			$base == self::get_homepage_link_by_locale($this->owner->Locale)) {
			
			$base = null;
		}
		
		// Legacy support
		if($action === true) $action = null;

		return Controller::join_links($base, '/', $action);
	}
	
	/**
	 * Get the absolute URL for this page, including protocol and host.
	 * alternateAbsoluteLink() is called from and will overrule 
	 * SiteTree::AbsoluteLink() default behaviour
	 *
	 * @param string $action See {@link Link()}
	 * @return string
	 */
	public function alternateAbsoluteLink($action = null) {
		return Director::absoluteURL($this->PrefixLink($action));
	}	

	/**
	 * Get the RelativeLink value for a home page in another locale. This is found by searching for the default home
	 * page in the default language, then returning the link to the translated version (if one exists).
	 *
	 * @return string
	 */
	public static function get_homepage_link_by_locale($locale) {

		$deflink = RootURLController::get_default_homepage_link();
		if ($locale == Translatable::default_locale()) {
			return $deflink;
		}
		$original = SiteTree::get_by_link($deflink);
		
		if(!empty($original)) {
			$homepage = $original->getTranslation($locale);
			if (empty($homepage)) {
				$homepage = SiteTree::get()->filter(array('Locale' => $locale))->First();
			}
			if (!empty($homepage)) return $homepage->URLSegment;
		}
		return '';	
	}	
}

class LanguagePrefix_Controller extends Extension {

	/**
	 * Return a link to the homepage for the current locale
	 * 
	 * @return string link to the homepage for the current locale 
	 */
	public function HomeLink() {
		return Controller::join_links(
			Director::baseURL(),
			LanguagePrefix::get_prefix($this->owner->Locale),
			'/'
		);
	}		
}

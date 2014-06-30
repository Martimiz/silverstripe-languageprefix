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
	public static $locale_prefix_map;

	/**
	 * ignore language prefix on the default locale
	 * 
	 * @var bool
	 */
	public static $ignore_default_locale;

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
	 * Get the locale -> prefix map from languageprefix.yml
	 */
	protected static function load_prefix_map() {
		self::$locale_prefix_map = Config::inst()->get('prefixconfig', 'locale_prefix_map'); 
	}		

	/**
	 * Load the prefix map before anything else happens
	 */
	public function __construct() {
		parent::__construct();
		self::load_prefix_map();

		// If duplicate URLSegments make Translatable allow that
		$enabled = Config::inst()->get('prefixconfig', 'enable_duplicate_urlsegments');
		if ($enabled) {
			Config::inst()->update('Translatable', 'enforce_global_unique_urls', false);
		}		
	}
	
	/**
	 * Deprecated: use Link() instead. 
	 */
	public function PrefixLink($action = null) {
		return $this->owner->Link();
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

	
	/**
	 * Add the language prefix to the relative link. Be sure to remove the
	 * homepage $URLSegment.
	 * 
	 * @param type $base  the relative link
	 * @param type $action 
	 */
	public function updateRelativeLink(&$base, &$action) {
		
		if(Config::inst()->get('prefixconfig', 'ignore_default_locale') && 
			$this->owner->Locale == Translatable::default_locale()) {
			return;
		}

		if (empty($action) && !$this->owner->ParentID &&
			$base == self::get_homepage_link_by_locale($this->owner->Locale)) {
			$base = null;
		}

		$prefix = self::get_prefix($this->owner->Locale);
			
		if (!preg_match("@^{$prefix}/@i", $base)) {
			$base = Controller::join_links(
				$prefix,
				$base
			);
		}
	}	
	
	/**
	 * Update the URLsegment in the CMS so it shows the correct language
	 * prefix in the URL.
	 * 
	 * @param FieldList $fields
	 */
	public function updateCMSFields(FieldList $fields) {
		
		$fields->removeByName('URLSegment');

		$prefix = self::get_prefix($this->owner->Locale);

		$baseLink = Controller::join_links (
			Director::absoluteBaseURL(),
			(SiteTree::nested_urls() && $this->owner->ParentID ? $this->owner->Parent()->RelativeLink(true) : $prefix . '/')
		);
				
		$urlsegment = new SiteTreeURLSegmentField("URLSegment", $this->owner->fieldLabel('URLSegment'));
		$urlsegment->setURLPrefix($baseLink);
		$helpText = (SiteTree::config()->nested_urls && count($this->owner->Children())) ? $this->owner->fieldLabel('LinkChangeNote') : '';
		if(!Config::inst()->get('URLSegmentFilter', 'default_allow_multibyte')) {
			$helpText .= $helpText ? '<br />' : '';
			$helpText .= _t('SiteTreeURLSegmentField.HelpChars', ' Special characters are automatically converted or removed.');
		}
		$urlsegment->setHelpText($helpText);
		
		$fields->addFieldToTab('Root.Main', $urlsegment, 'MenuTitle');		
	}
	
	/**
	 * Return a link to the homepage for the current locale
	 * 
	 * @return string link to the homepage for the current locale 
	 */
	public function BaseLinkForLocale() {
		return Controller::join_links(
			Director::baseURL(),
			LanguagePrefix::get_prefix($this->owner->Locale),
			'/'
		);
	}

	/**
	 * @deprecated Use BaseLinkForLocale() instead
	 */
	public function HomeLink() {
		return $this->BaseLinkForLocale();
	}
	
	/**
	 * Get the prefix out of the way. 
	 * 
	 * Note: this will fail for hamepage links like '/en_US/' because 
	 * Director::get_homepage_link() is now not called from get_by_link() 
	 * There is no way alternateGetByLink can influence the other parts 
	 * of the link. So you'll always end up on the default locale homepage.
	 *
	 * @param string $URLSegment
	 * @param int|null $parentID
	 * @return SiteTree
	 */
	public function alternateGetByLink($URLSegment, $parentID) {

		// if this is a valid prefix, just return an empty SiteTree object
		// SiteTree::get_by_link() will then traverse the other URL parts, 
		// starting with the urlsegment following the prefix!
		if (empty($parentID) && $URLSegment && self::valid_prefix($URLSegment)) {
			return singleton('SiteTree');
		}
		
		// no valid prefix? Leave it to the oter extensions...
		return false;
	}	
	
	/**
	 * Is the prefix a valid locale? (@see alternateGetByLink)
	 * 
	 * @param string $prefix
	 * @return boolean
	 */
	public static function valid_prefix($prefix) {

		// is this part of the prefix map if there is one?
		if (!empty(self::$locale_prefix_map)) 
			return(in_array($prefix, self::$locale_prefix_map));

		// is this part of the allowed languages, if there are any
		$alowed_locales = Translatable::get_allowed_locales();
		if (!empty($alowed_locales))
			return (in_array($prefix, $alowed_locales));

		// is this a valid locale anyway?
		return (i18n::validate_locale($prefix));
	}	
}


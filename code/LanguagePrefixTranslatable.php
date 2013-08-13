<?php

class LanguagePrefixTranslatable extends Translatable {
    
	/**
	 * Extends the SiteTree::validURLSegment() method, to do checks appropriate
	 * to Translatable and Languageprefix
         * 
         * This fix allowes you to use the same URLSegment in different translations
	 * 
	 * @return bool
     */
	public function augmentValidURLSegment() {
		$reDisableFilter = false;
		if(!self::locale_filter_enabled()) {
			self::enable_locale_filter();
			$reDisableFilter = true;
		}
		$IDFilter     = ($this->owner->ID) ? "AND \"SiteTree\".\"ID\" <> {$this->owner->ID}" :  null;
		$parentFilter = null;

		if($this->owner->ParentID) {
			$parentFilter = " AND \"SiteTree\".\"ParentID\" = {$this->owner->ParentID}";
		} else {
			$parentFilter = ' AND "SiteTree"."ParentID" = 0';
		}

		$existingPage = SiteTree::get()
			// disable get_one cache, as this otherwise may pick up results from when locale_filter was on
			->where("\"URLSegment\" = '{$this->owner->URLSegment}' $IDFilter $parentFilter")->First();
		
		if($reDisableFilter) self::disable_locale_filter();
		
		return !$existingPage;
	}    
	
	/**
	 * Subclasses Translatable::CreateTranslation() 
	 * 
	 * Creates a new translation for the owner object of this decorator.
	 * Checks {@link getTranslation()} to return an existing translation
	 * instead of creating a new one.
	 * 
	 * Will automatically create the translation's URLSegment as a duplicate 
	 * of the original pages' URLSegment 
	 * 
	 * @param string $locale Target locale to translate this object into
	 * @param boolean $saveTranslation Flag indicating whether the new record 
	 * should be saved to the database.
	 * @return DataObject The translated object
	 */
	function createTranslation($locale, $saveTranslation = true) {
		if($locale && !i18n::validate_locale($locale)) {
			throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		}
		
		if(!$this->owner->exists()) {
			user_error(
				'Translatable::createTranslation(): Please save your record before creating a translation', 
				E_USER_ERROR
			);
		}
		
		// permission check
		if(!$this->owner->canTranslate(null, $locale)) {
			throw new Exception(sprintf(
				'Creating a new translation in locale "%s" is not allowed for this user',
				$locale
			));
			return;
		}
		
		$existingTranslation = $this->getTranslation($locale);
		if($existingTranslation) return $existingTranslation;
		
		$class = $this->owner->class;
		$newTranslation = new $class;
		
		// copy all fields from owner (apart from ID)
		$newTranslation->update($this->owner->toMap());
		
		// If the object has Hierarchy extension,
		// check for existing translated parents and assign
		// their ParentID (and overwrite any existing ParentID relations
		// to parents in other language). If no parent translations exist,
		// they are automatically created in onBeforeWrite()
		if($newTranslation->hasField('ParentID')) {
			$origParent = $this->owner->Parent();
			$newTranslationParent = $origParent->getTranslation($locale);
			if($newTranslationParent) $newTranslation->ParentID = $newTranslationParent->ID;
		}
		
		$newTranslation->ID = 0;
		$newTranslation->Locale = $locale;
		
		$originalPage = $this->getTranslation(self::default_locale());
		if ($originalPage) {
			$urlSegment = $originalPage->URLSegment;
		} else {
			$urlSegment = $newTranslation->URLSegment;
		}
		$newTranslation->URLSegment = $urlSegment;

		// hacky way to set an existing translation group in onAfterWrite()
		$translationGroupID = $this->getTranslationGroup();
		$newTranslation->_TranslationGroupID = $translationGroupID ? $translationGroupID : $this->owner->ID;
		if($saveTranslation) $newTranslation->write();
		
		// run callback on page for translation related hooks
		$newTranslation->invokeWithExtensions('onTranslatableCreate', $saveTranslation);
		
		return $newTranslation;
	}	
    
}

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
    
}

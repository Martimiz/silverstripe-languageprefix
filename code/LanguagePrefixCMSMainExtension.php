<?php
/**
 * Temporary extension to make sure the orrect locale is used in the page EditForm
 * Solves an issue where duplicate URLSegments, when enabled, are validated for 
 * the wrong locale
 */
class LanguagePrefixCMSMainExtension extends Extension {
	function updateEditForm(&$form) {
		if($form->getName() == 'EditForm' && SiteTree::has_extension("LanguagePrefixTranslatable")) {
			$form->Fields()->push(new HiddenField('locale','', Translatable::get_current_locale()));
		}
	}
}	


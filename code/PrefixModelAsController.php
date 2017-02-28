<?php

/**
 * @package languageprefix
 */
class PrefixModelAsController extends ModelAsController
{

    /**
     * The locale that is distilled from the URL's language prefix.
     * @var string
     */
    protected $locale = 'en_US';

    /**
     * @var string
     */
    protected static $default_homepage_link = 'home';

    /**
     * @uses ModelAsController::getNestedController()
     * @param SS_HTTPRequest $request
     * @param DataModel $model
     * @return SS_HTTPResponse
     */
    public function handleRequest(SS_HTTPRequest $request, DataModel $model)
    {

        // Check Translatable dependency
        if (!class_exists('Translatable') ||
            (!SiteTree::has_extension('Translatable') &&
            !SiteTree::has_extension('LanguagePrefixTranslatable'))
        ) {
            throw new Exception('Dependency error: the LanguagePrefix module expects the Translatable module.');
        }

        $disablePrefixForDefaultLang = Config::inst()->get('prefixconfig', 'disable_prefix_for_default_lang');

        $firstSegment = $request->param('URLSegment');

        if ($firstSegment) {
            $prefixUsed = $this->setLocale($firstSegment);

            $defaultLocale = Translatable::default_locale();
            $isDefaultLocale = ($this->locale == $defaultLocale);

            if ($prefixUsed) {
                if ($isDefaultLocale && $disablePrefixForDefaultLang) {
                    $url = substr($request->getURL(true), strlen($firstSegment));
                    return $this->redirect($url, 301);
                } else {
                    $request->shiftAllParams();
                    $request->shift(1);
                }
            } else {
                if(class_exists('RedirectedURL')){
                    $redirect=new RedirectedURLHandler();
                    $redirect->onBeforeHTTPError404($request);
                }
            }
        }

        return parent::handleRequest($request, $model);
    }

    /**
     * Try to determine the controller for the current request.
     *
     * @return type Controller
     */
    public function getNestedController()
    {

        $URLSegment = $this->request->param('URLSegment');

        if (empty($URLSegment)) {
            PrefixRootURLController::set_is_at_root();

            // get the homepage from the defaul homepage Translation Group
            $URLSegment = LanguagePrefix::get_homepage_link_by_locale($this->locale);

            // if no homepage is found in the default translation group for this locale
            // use the first page in the tree instead
            if (empty($URLSegment)) {
                //@TODO: make 3.0
                $sitetree = SiteTree::get()
                    ->filter(array('ParentID' => '0', 'Locale' => $this->locale))
                    ->sort('Sort')
                    ->First();

                if ($sitetree) {
                    $URLSegment = $sitetree->URLSegment;
                } else {
                    // last resort
                    $URLSegment = self::$default_homepage_link;
                }
            }
        }

        // We have an URLSegment: find the page with this segment - within the current locale
        // In the original ModelAsController the locale filter is disabled for this query,
        // meaning /nl/englishHomePage/ will be found and be redirected later on
        // to /en/englishHomePage/ where I'd rather have a 404!!
        Translatable::enable_locale_filter();

        // make sure multibyte urls are supported
        $sitetree = DataObject::get_one(
                'SiteTree', sprintf(
                    '"SiteTree"."URLSegment" = \'%s\' %s',
                    Convert::raw2sql(rawurlencode($URLSegment)),
                    (SiteTree::config()->nested_urls ? 'AND "SiteTree"."ParentID" = 0' : null)
                )
        );

        // As in the original ModelAsController: if no page can be found, check if it
        // has been renamed or relocated - again: within the current locale!!!
        // If the current $URLSegment refers to an 'old page', do a 302 redirect to the
        // current version (this works for bookmarked pages)
        // Note: for this the find_old_page() function needs to be localized as well to
        // find_old_page_localized()
        if (empty($sitetree)) {

            if ($redirect = self::find_old_page_localized($URLSegment)) {
                $params = $this->request->getVars();
                if (isset($params['url'])) {
                    unset($params['url']);
                }
                $this->response = new SS_HTTPResponse();
                $this->response->redirect(
                    Controller::join_links(
                        $redirect->Link(
                            Controller::join_links(
                                $this->request->param('Action'),
                                $this->request->param('ID'),
                                $this->request->param('OtherID')
                            )
                        ),
                        // Needs to be in separate join links to avoid urlencoding
                        ($params) ? '?' . http_build_query($params) : null
                    ), 301
                );

                return $this->response;
            }

            if(class_exists('RedirectedURL')){
                $redirect=new RedirectedURLHandler();
                $redirect->onBeforeHTTPError404($this->request);
            }
            // all is now lost!
            return $this->showPageNotFound();
        }

        // This we don't really need anymore...
        // Enforce current locale setting to the loaded SiteTree object
        // if($sitetree->Locale) Translatable::set_current_locale($sitetree->Locale);

        if (isset($_REQUEST['debug'])) {
            Debug::message("Using record #$sitetree->ID of type $sitetree->class with link {$sitetree->Link()}");
        }

        return self::controller_for($sitetree, $this->request->param('Action'));
    }

    /**
     * Distill the locale from the URL's language prefix. If the prefix isn't a
     * proper locale, return false
     *
     * @return boolean valid locale found
     */
    protected function setLocale($prefix)
    {

        if ($locale = LanguagePrefix::get_locale_from_prefix($prefix)) {
            $this->locale = $locale;
            Translatable::set_current_locale($this->locale);
            return true;
        } else {
            return false;
        }
    }

    /**
     *  try to show a proper 404 error page. If the locale doesn't exist or no errorpage
     *  exists for the current locale, show the 404 error page for the default locale
     *  this will not redirect! If this is the right approach is up for discussion...
     */
    protected function showPageNotFound()
    {

        if ($response = ErrorPage::response_for(404)) {
            return $response;
        }
        // if an errorpage is not defined for the current language
        // use the default language
        $locale = Translatable::default_locale();
        Translatable::set_current_locale($locale);

        if ($response = ErrorPage::response_for(404)) {
            return $response;
        }
        //return $this->httpError(404, 'The requested page could not be found!!');
    }

    /**
     * This version takes into account that the old page must have the same locale as the new one
     * As aSQLQuery is used, that doesn't autmatically respond to the locale_filter,
     * the ModelAsController::find_old_page() function needed to be extended
     *
     * @param string $URLSegment A subset of the url. i.e in /home/contact/ home and contact are URLSegment.
     * @param int $parentID The ID of the parent of the page the URLSegment belongs to.
     * @return SiteTree
     *
     */
    static function find_old_page_localized($URLSegment, $parentID = 0, $ignoreNestedURLs = false)
    {

        $URLSegment = Convert::raw2sql($URLSegment);
        $Locale = Translatable::get_current_locale();

        $useParentIDFilter = SiteTree::nested_urls() && $parentID;

        // First look for a non-nested page that has a unique URLSegment and can be redirected to.
        if (SiteTree::nested_urls()) {
            $filter = array('URLSegment' => $URLSegment);
            if ($useParentIDFilter) {
                $filter['ParentID'] = (int) $parentID;
            }
            $pages = SiteTree::get()->filter($filter);

            if ($pages && $pages->Count() == 1) {
                return $pages->First();
            };
        }

        // Get an old version of a page that has been renamed. Make it localized!
        $parentIDFilter = ($useParentIDFilter) ? ' AND "ParentID" = ' . (int) $parentID : '';

        $query = new SQLQuery(
            '"RecordID"',
            '"SiteTree_versions"',
            "\"URLSegment\" = '$URLSegment' AND \"Locale\" = '$Locale' AND \"WasPublished\" = 1" . $parentIDFilter,
            '"LastEdited" DESC',
            null,
            null,
            1
        );
        $record = $query->execute()->first();

        if ($record && ($oldPage = DataObject::get_by_id('SiteTree', $record['RecordID']))) {
            // Run the page through an extra filter to ensure that all decorators are applied.
            if (SiteTree::get_by_link($oldPage->RelativeLink())) {
                return $oldPage;
            }
        }
    }

}

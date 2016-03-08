<?php
namespace Ibrows\BoxalinoBundle\Helper;

use com\boxalino\bxclient\v1\BxAutocompleteRequest;
use com\boxalino\bxclient\v1\BxAutocompleteResponse;
use com\boxalino\bxclient\v1\BxChooseResponse;
use com\boxalino\bxclient\v1\BxFacets;
use com\boxalino\bxclient\v1\BxFilter;
use com\boxalino\bxclient\v1\BxRecommendationRequest;
use com\boxalino\bxclient\v1\BxRequest;
use com\boxalino\bxclient\v1\BxSearchRequest;
use com\boxalino\p13n\api\thrift\AutocompleteHit;
use com\boxalino\p13n\api\thrift\AutocompleteResponse;
use Ibrows\BoxalinoBundle\Lib\BoxalinoClient;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HttpP13nService
 * @package Ibrows\BoxalinoBundle\Client
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class HttpP13nHelper
{

    const FACET_TYPE_PRICE = 'price';
    const FACET_TYPE_CATEGORY = 'category';
    const FACET_TYPE_STRING = 'string';
    /**
     * @var BoxalinoClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $host = 'cdn.bx-cloud.com';

    /**
     * @var bool
     */
    protected $relaxationEnabled = true;

    /**
     * @var string
     */
    protected $searchWidgetId = 'search';

    /**
     * @var string
     */
    protected $autocompleteWidgetId = 'autocomplete';

    /**
     * @var string
     */
    protected $language = 'en';

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $profileId;

    /**
     * @var Cookie
     */
    protected $cemsCookie;

    /**
     * @var Cookie
     */
    protected $cemvCookie;

    /**
     * HttpP13nHelper constructor.
     * @param RequestStack $requestStack
     * @param $account
     * @param $username
     * @param $password
     */
    public function __construct(RequestStack $requestStack, $account, $username, $password)
    {
        $this->account = $account;
        $this->username = $username;
        $this->password = $password;
        $this->setRequest($requestStack);
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();

        if ($this->request) {
            $this->setLanguage($this->request->getLocale());
            $this->domain = $this->request->getHost();
        }
    }

    /**
     * @param array $returnFields
     * @param null $queryText
     * @param int $offset
     * @param int $hitCount
     * @param array $filters
     * @param array $facets
     * @param array $sortFields
     * @param bool|false $orFilters
     * @return \com\boxalino\bxclient\v1\BxChooseResponse
     */
    public function search(array $returnFields, $queryText = null, $offset = 0, $hitCount = 12,
                           $filters = array(), $facets = array(), $sortFields = array(), $orFilters = false)
    {
        $bxRequest = $this->createSearchRequest($returnFields, $queryText, $offset, $hitCount, $filters, $facets,
            $sortFields, $orFilters);

        $this->addRequest($bxRequest);

        // Call the service
        return $this->getResponse();
    }

    public function createSearchRequest(array $returnFields, $queryText = null, $offset = 0, $hitCount = 12,
                                        $filters = array(), $facets = array(), $sortFields = array(), $orFilters = false)
    {
        $bxRequest = new BxSearchRequest($this->language, $queryText, $hitCount);
        $bxRequest->setIndexId($this->account);
        $bxRequest->setReturnFields($returnFields);
        $bxRequest->setOffset($offset);

        foreach ($filters as $filter) {
            $this->addFilter($bxRequest, $filter);
        }
        $bxRequest->setOrFilters($orFilters);
        $this->setFacets($bxRequest, $facets);
        foreach ($sortFields as $sortField) {
            $reverse = array_key_exists('reverse', $sortField) ? true : false;
            $bxRequest->addSortField($sortField['fieldName'], $reverse);
        }

        return $bxRequest;
    }

    /**
     * @param BxSearchRequest $bxRequest
     */
    public function addRequest(BxSearchRequest $bxRequest)
    {
        $this->getClient()->addRequest($bxRequest);
    }

    /**
     * @param array $returnFields
     * @param $queryText
     * @param int $hitCount
     * @param int $suggestionCount
     * @param array $filters
     * @return null
     */
    public function autocomplete(array $returnFields, $queryText, $hitCount = 5, $suggestionCount = 5, $filters = array())
    {
        $bxAutocompleteRequest = $this->createAutocompleteRequest($returnFields, $queryText, $hitCount, $suggestionCount, $filters);

        //make the query to Boxalino server and get back the response for all requests
        return $this->getAutocompleteResponse($bxAutocompleteRequest);
    }

    /**
     * @param array $returnFields
     * @param $queryText
     * @param int $hitCount
     * @param int $suggestionCount
     * @return BxAutocompleteRequest
     */
    public function createAutocompleteRequest(array $returnFields, $queryText, $hitCount = 5, $suggestionCount = 5, $filters = array())
    {
        $bxRequest = new BxAutocompleteRequest($this->language, $queryText, $hitCount, $suggestionCount,
            $this->autocompleteWidgetId, $this->searchWidgetId);

        //set the fields to be returned for each item in the response
        $bxRequest->getBxSearchRequest()->setReturnFields($returnFields);


        foreach ($filters as $filter) {
            $this->addFilter($bxRequest->getBxSearchRequest(), $filter);
        }

        return $bxRequest;
    }

    /**
     * @param BxAutocompleteRequest $bxAutocompleteRequest
     * @return null
     */
    public function getAutocompleteResponse(BxAutocompleteRequest $bxAutocompleteRequest)
    {
        $this->getClient()->setAutocompleteRequest($bxAutocompleteRequest);

        return $this->getClient()->getAutocompleteResponse();
    }

    /**
     * @param array $requests
     * @return AutocompleteResponse
     */
    public function getAutocompleteResponses(array $requests)
    {
        $this->getClient()->setAutocompleteRequests($requests);

        return $this->getClient()->getAutocompleteResponses();
    }

    /**
     * @param BxAutocompleteResponse $bxAutocompleteResponse
     * @return array
     */
    public function getAutocompleteResults(BxAutocompleteResponse $bxAutocompleteResponse)
    {
        return $bxAutocompleteResponse->getResponse()->hits;
    }

    /**
     * Retrieve recommendations based on an id and/or selected context
     *
     * @param array $returnFields
     * @param $id
     * @param int $offset
     * @param int $hitCount
     * @param string $fieldName
     * @param array $contexts
     * @return \com\boxalino\bxclient\v1\BxChooseResponse
     */
    public function findRawRecommendations(array $returnFields, $id, $offset = 0, $hitCount = 5,
                                           $fieldName = 'id', $contexts = array('search'))
    {
        foreach ($contexts as $context) {
            $bxRequestSimilar = new BxRecommendationRequest($this->language, $context, $hitCount);
            $bxRequestSimilar->setOffset($offset);
            $bxRequestSimilar->setReturnFields($returnFields);
            //indicate the product the user is looking at now (reference of what the recommendations need to be similar to)
            $bxRequestSimilar->setProductContext($fieldName, $id);
            //add the request
            $this->getClient()->addRequest($bxRequestSimilar);
        }

        return $this->getResponse();
    }

    /**
     * Get extra results if results too little from sugestions
     *
     * @param BxChooseResponse $chooseResponse
     * @param null $choiceId
     * @return array
     */
    public function getRelaxationSuggestionResults(BxChooseResponse $chooseResponse, $choiceId = null)
    {
        $suggestions = array();
        if (!$this->relaxationEnabled) {
            return $suggestions;
        }
        $variant = $chooseResponse->getChoiceResponseVariant($choiceId);
        if ($variant->searchRelaxation) {
            $suggestions = $variant->searchRelaxation->suggestionsResults;
        }
        return $suggestions;
    }

    /**
     * Get extra results if results too little from sub-phrases
     *
     * @param BxChooseResponse $chooseResponse
     * @param null $choiceId
     * @return array
     */
    public function getRelaxationSubphraseResults(BxChooseResponse $chooseResponse, $choiceId = null)
    {
        $subphrases = array();
        if (!$this->relaxationEnabled) {
            return $subphrases;
        }
        $variant = $chooseResponse->getChoiceResponseVariant($choiceId);
        if ($variant->searchRelaxation && $variant->searchRelaxation->subphrasesResults) {
            $subphrases = $variant->searchRelaxation->subphrasesResults;
        }

        return $subphrases;
    }

    /**
     * @param BxRequest $bxRequest
     * @param array|BxFilter $filter
     * @param bool|false $negative
     */
    protected function addFilter(BxRequest $bxRequest, $filter, $negative = false)
    {
        if (!$filter instanceof BxFilter) {
            $filter = new BxFilter($filter['fieldName'], $filter['values'], $negative);
        }

        $bxRequest->addFilter($filter);
    }

    /**
     * @param BxRequest $bxRequest
     * @param $facets
     * @param null $label
     * @param int $order
     */
    protected function setFacets(BxRequest $bxRequest, $facets, $label = null, $order = 0)
    {
        $bxFacets = new BxFacets();

        foreach ($facets as $facet) {
            $selectedValue = array_key_exists('values', $facet) ? $facet['values'] : null;
            $type = array_key_exists('type', $facet) ? $facet['type'] : self::FACET_TYPE_STRING;

            switch ($type){
                case self::FACET_TYPE_PRICE:
                    $bxFacets->addRangedFacet($facet['fieldName'], $selectedValue, $label, $order);
                    break;
                case self::FACET_TYPE_CATEGORY:
                    if($selectedValue) {
                        $bxFacets->addFacet('category_id', $selectedValue, 'hierarchical', '1');
                    }
                    $bxFacets->addFacet($facet['fieldName'], null, 'hierarchical', $order);
                    break;
                case self::FACET_TYPE_STRING:
                    $bxFacets->addFacet($facet['fieldName'], $selectedValue, $type, $label, $order);
                    break;
            }

        }

        $bxRequest->setFacets($bxFacets);

    }

    /**
     * @param BxChooseResponse $chooseResponse
     * @param $fieldName
     * @param null $choiceId
     * @return array
     */
    public function extractFacet(BxChooseResponse $chooseResponse, $fieldName, $choiceId = null)
    {
        $facets = $chooseResponse->getFacets($choiceId, $this->relaxationEnabled);

        return $this->getFacetValues($facets, $fieldName);

    }

    /**
     * @param BxFacets $facets
     * @param $fieldName
     * @return array
     */
    protected function getFacetValues(BxFacets $facets, $fieldName)
    {
        $facetArray = array();
        //loop on the search response hit ids and print them
        foreach ($facets->getFacetValues($fieldName) as $fieldValue) {
            $facetArray[] = array(
                'parameterValue' => $facets->getFacetValueParameterValue($fieldName, $fieldValue),
                'stringValue' => $facets->getFacetValueLabel($fieldName, $fieldValue),
                'selected' => $facets->isFacetValueSelected($fieldName, $fieldValue),
                'hitCount' => $facets->getFacetValueCount($fieldName, $fieldValue),
            );
        }

        return $facetArray;
    }

    /**
     * @param BxChooseResponse $chooseResponse
     * @param $fieldName
     * @param null $choiceId
     * @return array
     */
    public function extractSuggestionFacet(BxChooseResponse $chooseResponse, $fieldName, $choiceId = null)
    {
        $suggetions = $this->getRelaxationSuggestionResults($chooseResponse, $choiceId);
        $facets = $chooseResponse->getFacets($choiceId, $this->relaxationEnabled);
        if (count($suggetions)) {
            $suggestion = $suggetions[0];
            $facets->setFacetResponse($suggestion->facetResponses);
        }

        return $this->getFacetValues($facets, $fieldName);
    }

    /**
     * @param BxChooseResponse $chooseResponse
     * @param $fieldName
     * @param null $choiceId
     * @return array
     */
    public function extractSubphraseFacet(BxChooseResponse $chooseResponse, $fieldName, $choiceId = null)
    {
        $subphrases = $this->getRelaxationSubphraseResults($chooseResponse);
        $facets = $chooseResponse->getFacets($choiceId, $this->relaxationEnabled);

        if (count($subphrases)) {
            $subphrase = $subphrases[0];
            $facets->setFacetResponse($subphrase->facetResponses);
        }

        return $this->getFacetValues($facets, $fieldName);
    }

    /**
     * @return BxChooseResponse
     */
    public function getResponse()
    {
        return $this->getClient()->getResponse();
    }

    /**
     * @param BxChooseResponse $chooseResponse
     * @param null $choiceId
     * @return \com\boxalino\p13n\api\thrift\SearchResult|null
     */
    public function extractResults(BxChooseResponse $chooseResponse, $choiceId = null)
    {
        $variant = $chooseResponse->getChoiceResponseVariant($choiceId);

        $results = $chooseResponse->getVariantSearchResult($variant, $this->relaxationEnabled);

        if(is_null($results)){
            $results = $variant->searchResult;
        }

        return $results;

    }

    /**
     * @return BoxalinoClient
     */
    public function getClient()
    {
        if (!$this->client) {

            $this->client = new BoxalinoClient(
                $this->username,
                $this->password,
                $this->domain
            );

            $this->client->setProfileId($this->getProfileId());
            $this->client->setSessionId($this->getSessionId());
        }

        return $this->client;
    }

    /**
     * @return string
     */
    public function getProfileId()
    {
        if (!$this->profileId) {
            $this->getCemvCookie();
        }

        return $this->profileId;
    }

    /**
     * @param Request|null $request
     * @return Cookie
     */
    public function getCemvCookie(Request $request = null)
    {
        if ($this->cemvCookie) {
            return $this->cemvCookie;
        }
        if (is_null($request)) {
            $request = $this->request;
        }
        if (!$request) {
            return '';
        }

        $this->setCemvCookie($request->cookies->get('cemv', null));
        return $this->cemvCookie;
    }

    /**
     * @param null $cemv
     */
    public function setCemvCookie($cemv = null)
    {
        $this->profileId = $cemv = is_null($cemv) ? $this->generateRandomId() : $cemv;
        $this->cemvCookie = new Cookie('cemv', $cemv, new \DateTime('+3 months'), '/', null, false, false);
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        if (!$this->sessionId) {
            $this->getCemsCookie();
        }

        return $this->sessionId;
    }

    /**
     * @param Request|null $request
     * @return Cookie
     */
    public function getCemsCookie(Request $request = null)
    {
        if ($this->cemsCookie) {
            return $this->cemsCookie;
        }
        if (is_null($request)) {
            $request = $this->request;
        }
        if (!$request) {
            return '';
        }
        $this->setCemsCookie($request->cookies->get('cems', null));
        return $this->cemsCookie;
    }

    /**
     * @param null $cems
     */
    public function setCemsCookie($cems = null)
    {
        $this->sessionId = $cems = is_null($cems) ? $this->generateRandomId(24) : $cems;
        $this->cemsCookie = new Cookie('cems', $cems, 0, '/', null, false, false);
    }

    /**
     * @param int $bytes
     * @return string
     */
    public function generateRandomId($bytes = 16)
    {
        $id = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $id = bin2hex(openssl_random_pseudo_bytes($bytes));
        }
        if (empty($id)) {
            $id = uniqid('', true);
        }

        return $id;
    }

    /**
     * @return boolean
     */
    public function isRelaxationEnabled()
    {
        return $this->relaxationEnabled;
    }

    /**
     * @param boolean $relaxationEnabled
     */
    public function setRelaxationEnabled($relaxationEnabled)
    {
        $this->relaxationEnabled = $relaxationEnabled;
    }

    /**
     * @return mixed
     */
    public function getSearchWidgetId()
    {
        return $this->searchWidgetId;
    }

    /**
     * @param mixed $searchWidgetId
     */
    public function setSearchWidgetId($searchWidgetId)
    {
        $this->searchWidgetId = $searchWidgetId;
    }

    /**
     * @return mixed
     */
    public function getAutocompleteWidgetId()
    {
        return $this->autocompleteWidgetId;
    }

    /**
     * @param mixed $autocompleteWidgetId
     */
    public function setAutocompleteWidgetId($autocompleteWidgetId)
    {
        $this->autocompleteWidgetId = $autocompleteWidgetId;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the language to search in either from request or manually
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = substr($language, 0, 2);
    }

}
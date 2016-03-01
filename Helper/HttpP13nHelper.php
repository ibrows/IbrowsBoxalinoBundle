<?php
namespace Ibrows\BoxalinoBundle\Helper;

use com\boxalino\bxclient\v1\BxAutocompleteRequest;
use com\boxalino\bxclient\v1\BxFacets;
use com\boxalino\bxclient\v1\BxFilter;
use com\boxalino\bxclient\v1\BxRecommendationRequest;
use com\boxalino\bxclient\v1\BxRequest;
use com\boxalino\bxclient\v1\BxSearchRequest;
use com\boxalino\p13n\api\thrift\AutocompleteHit;
use com\boxalino\p13n\api\thrift\AutocompleteQuery;
use com\boxalino\p13n\api\thrift\AutocompleteRequest;
use com\boxalino\p13n\api\thrift\AutocompleteResponse;
use com\boxalino\p13n\api\thrift\ChoiceInquiry;
use com\boxalino\p13n\api\thrift\ChoiceRequest;
use com\boxalino\p13n\api\thrift\ChoiceResponse;
use com\boxalino\p13n\api\thrift\ContextItem;
use com\boxalino\p13n\api\thrift\FacetRequest;
use com\boxalino\p13n\api\thrift\FacetValue;
use com\boxalino\p13n\api\thrift\Filter;
use com\boxalino\p13n\api\thrift\RequestContext;
use com\boxalino\p13n\api\thrift\SimpleSearchQuery;
use com\boxalino\p13n\api\thrift\SortField;
use com\boxalino\p13n\api\thrift\UserRecord;
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
    /**
     * @var BoxalinoClient
     */
    protected $client;

    /**
     * @var
     */
    protected $account;

    /**
     * @var
     */
    protected $username;

    /**
     * @var
     */
    protected $password;

    /**
     * @var
     */
    protected $host = 'cdn.bx-cloud.com';

    /**
     * @var bool
     */
    protected $relaxationEnabled = true;

    /**
     * @var string
     */
    protected $highlightPreTag = '<em>';

    /**
     * @var string
     */
    protected $highlightPostTag = '</em>';

    /**
     * @var
     */
    protected $searchWidgetId = 'search';

    /**
     * @var
     */
    protected $autocompleteWidgetId = 'autocomplete';

    /**
     * @var string
     */
    protected $language = 'en';

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

        $this->setLanguage($this->request->getLocale());

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

        $this->getClient()->addRequest($bxRequest);

        // Call the service
        return $this->getResponse();
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
     */
    protected function setFacets(BxRequest $bxRequest, $facets)
    {
        $bxFacets = new BxFacets();

        foreach ($facets as $facet) {
            if (array_key_exists('values', $facet)) {
                $bxFacets->addFacet($facet['fieldName'], $facet['values']);
            } else {
                $bxFacets->addFacet($facet['fieldName']);
            }
        }

        $bxRequest->setFacets($bxFacets);

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
                $this->request->getHost(),
                $this->language
            );

            $this->client->setProfileId($this->getProfileId());
            $this->client->setSessionId($this->getSessionId());
        }

        return $this->client;
    }

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

    public function getResponse()
    {
        return $this->getClient()->getResponse();
    }

    /**
     * @param $choiceResponse
     * @param array $choiceIds
     * @return array
     */
    public function extractResults($choiceResponse, $choiceIds = array())
    {
        $results = array();
        $count = 0;
        $choiceIdCount = is_array($choiceIds) ? count($choiceIds) : 0;
        /** @var \com\boxalino\p13n\api\thrift\Variant $variant */
        foreach ($choiceResponse->variants as $variant) {
            /** @var \com\boxalino\p13n\api\thrift\SearchResult $searchResult */
            $searchResult = $variant->searchResult;
            if ($choiceIdCount) {
                list($configOption, $choiceId) = each($choiceIds);
                $results[$configOption] = array(
                    'results' => $this->extractResultsFromHits($searchResult->hits),
                    'count' => $searchResult->totalHitCount,
                    '_widgetTitle' => $variant->searchResultTitle
                );
            } else {
                $count += $searchResult->totalHitCount;
                $this->extractResultsFromHits($searchResult->hits, $results);
            }
            // Widget's meta data, mostly used for event tracking
        }
        if ($choiceIdCount) {
            return $results;
        } else {
            return array('results' => $results, 'count' => $count);
        }
    }

    public function extractResultsFromHits($hits, &$results = array())
    {
        /** @var \com\boxalino\p13n\api\thrift\Hit $item */
        foreach ($hits as $item) {
            $result = array();
            foreach ($item->values as $key => $value) {
                if (is_array($value) && count($value) == 1) {
                    $result[$key] = array_shift($value);
                } else {
                    $result[$key] = $value;
                }
            }
            $results[] = $result;
        }

        return $results;
    }

    public function extractFacet(BxFacets $facets, $fieldName)
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
     * @param array $returnFields
     * @param $queryText
     * @param int $hitCount
     * @return AutocompleteResponse
     */
    public function autocomplete(array $returnFields, $queryText, $hitCount = 5, $suggestionCount = 5)
    {
        $bxRequest = new BxAutocompleteRequest($this->language, $queryText, $hitCount, $suggestionCount,
            $this->autocompleteWidgetId, $this->searchWidgetId);

        //set the fields to be returned for each item in the response
        $bxRequest->getBxSearchRequest()->setReturnFields($returnFields);

        //set the request
        $this->getClient()->setAutocompleteRequest($bxRequest);

        //make the query to Boxalino server and get back the response for all requests
        return $this->getClient()->getAutocompleteResponse();
    }

    /**
     * Retrieve the suggestions from the autocomplete response
     *
     * @param AutocompleteResponse $response
     * @return array
     */
    public function getAutocompleteSuggestions(AutocompleteResponse $response)
    {
        $suggestions = array();
        /** @var AutocompleteHit $hit */
        foreach ($response->hits as $hit) {
            $results = array();
            $this->extractResultsFromHits($hit->searchResult->hits, $results);
            $suggestions[] = array(
                'text' => $hit->suggestion,
                'html' => (strlen($hit->highlighted) ? $hit->highlighted : $hit->suggestion),
                'searchResults' => $results,
                'hits' => $hit->searchResult->totalHitCount,
            );
        }
        return $suggestions;
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
        foreach($contexts as $context){
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
     * @return ChoiceRequest
     */
    public function getChoiceRequest()
    {
        $choiceRequest = new ChoiceRequest();

        // Setup information about account
        $userRecord = new UserRecord();
        $userRecord->username = $this->account;
        $choiceRequest->userRecord = $userRecord;

        return $choiceRequest;
    }

    /**
     * @return \com\boxalino\bxclient\v1\BxChooseResponse
     */
    public function choose()
    {
        return $this->getClient()->getResponse();
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

    /**
     * Get extra results if results too little from sugestions
     *
     * @param ChoiceResponse $response
     * @return array
     */
    public function getRelaxationSuggestions(ChoiceResponse $response)
    {
        $suggestions = array();
        if ($this->relaxationEnabled) {
            /** @var \com\boxalino\p13n\api\thrift\Variant $variant */
            foreach ($response->variants as $variant) {
                if (is_object($variant->searchRelaxation)) {
                    /** @var \com\boxalino\p13n\api\thrift\SearchResult $searchResult */
                    foreach ($variant->searchRelaxation->suggestionsResults as $searchResult) {
                        $suggestions[] = array(
                            'text' => $searchResult->queryText,
                            'count' => $searchResult->totalHitCount,
                            'results' => $this->extractResultsFromHitGroups($searchResult->hitsGroups),
                        );
                    }
                }
            }
        }
        return $suggestions;
    }

    public function extractResultsFromHitGroups($hitsGroups, &$results = array())
    {
        if (!is_array($hitsGroups)) {
            return $results;
        }

        /** @var \com\boxalino\p13n\api\thrift\HitsGroup $group */
        foreach ($hitsGroups as $group) {
            $this->extractResultsFromHits($group->hits, $results);
        }
        return $results;
    }

    /**
     * Get extra results if results too little from sub-phrases
     *
     * @param ChoiceResponse $response
     * @return array
     */
    public function getRelaxationSubphraseResults(ChoiceResponse $response)
    {
        $subphrases = array();
        if ($this->relaxationEnabled) {
            /** @var \com\boxalino\p13n\api\thrift\Variant $variant */
            foreach ($response->variants as $variant) {
                if (is_object($variant->searchRelaxation)) {
                    /** @var \com\boxalino\p13n\api\thrift\SearchResult $searchResult */
                    foreach ($variant->searchRelaxation->subphrasesResults as $searchResult) {
                        $subphrases[] = array(
                            'text' => $searchResult->queryText,
                            'count' => $searchResult->totalHitCount,
                            'results' => $this->extractResultsFromHitGroups($searchResult->hitsGroups),
                        );
                    }
                }
            }
        }
        return $subphrases;
    }

}
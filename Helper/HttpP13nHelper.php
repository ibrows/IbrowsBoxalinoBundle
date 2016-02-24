<?php
namespace Ibrows\BoxalinoBundle\Helper;

use com\boxalino\p13n\api\thrift\AutocompleteHit;
use com\boxalino\p13n\api\thrift\AutocompleteQuery;
use com\boxalino\p13n\api\thrift\AutocompleteRequest;
use com\boxalino\p13n\api\thrift\AutocompleteResponse;
use com\boxalino\p13n\api\thrift\ChoiceInquiry;
use com\boxalino\p13n\api\thrift\ChoiceRequest;
use com\boxalino\p13n\api\thrift\ContextItem;
use com\boxalino\p13n\api\thrift\FacetRequest;
use com\boxalino\p13n\api\thrift\Filter;
use com\boxalino\p13n\api\thrift\RequestContext;
use com\boxalino\p13n\api\thrift\SimpleSearchQuery;
use com\boxalino\p13n\api\thrift\SortField;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Thrift\HttpP13n;

/**
 * Class HttpP13nService
 * @package Ibrows\BoxalinoBundle\Client
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class HttpP13nHelper
{
    /**
     * @var HttpP13n
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
     * HttpP13nService constructor.
     * @param $account
     * @param $username
     * @param $password
     */
    public function __construct($account, $username, $password)
    {
        $this->account = $account;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param null $cemv
     * @return Cookie
     */
    public function createCemvCookie($cemv = null)
    {
        $cemv = is_null($cemv) ? $this->generateRandomId() : $cemv;
        return new Cookie('cemv', $cemv, new \DateTime('+3 months'), '/', null, false, false);
    }

    /**
     * @param null $cems
     * @return Cookie
     */
    public function createCemsCookie($cems = null)
    {
        $cems = is_null($cems) ? $this->generateRandomId(24) : $cems;
        return new Cookie('cems', $cems, 0, '/', null, false, false);
    }

    /**
     * @param int $bytes
     * @return string
     */
    public function generateRandomId($bytes = 16){
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
     * @param array $returnFields
     * @param null $queryText
     * @param int $offset
     * @param int $hitCount
     * @param array $filters
     * @param array $facets
     * @param array $sortFields
     * @return \com\boxalino\p13n\api\thrift\ChoiceResponse
     */
    public function search(array $returnFields, $queryText = null, $offset = 0, $hitCount = 12,
                           $filters = array(), $facets = array(), $sortFields = array())
    {
        $choiceRequest = $this->getChoiceRequest();
        // Setup main choice inquiry object
        $inquiry = new ChoiceInquiry();
        $inquiry->choiceId = $this->searchWidgetId;
        $inquiry->withRelaxation = $this->relaxationEnabled;

        $searchQuery = $this->getSimpleSearchQuery($returnFields, $queryText, $offset, $hitCount);


        foreach ($filters as $filter) {
            $this->addFilter($searchQuery, $filter);
        }

        foreach ($facets as $facet) {
            $this->addFacet($searchQuery, $facet);
        }

        foreach ($sortFields as $sortField) {
            $this->addSortField($searchQuery, $sortField);
        }

        // Connect search query to the inquiry
        $inquiry->simpleSearchQuery = $searchQuery;


        // Add inquiry to choice request
        $choiceRequest->inquiries = array($inquiry);

        // Call the service
        return $this->choose($choiceRequest);

    }

    /**
     * @return ChoiceRequest
     */
    public function getChoiceRequest()
    {
        $choiceRequest = new \com\boxalino\p13n\api\thrift\ChoiceRequest();

        // Setup information about account
        $userRecord = new \com\boxalino\p13n\api\thrift\UserRecord();
        $userRecord->username = $this->account;
        $choiceRequest->userRecord = $userRecord;

        return $choiceRequest;
    }

    /**
     * @return HttpP13n
     */
    public function getClient()
    {

        if (!$this->client) {
            $this->client = new HttpP13n();
            $this->client->setHost($this->host);
            $this->client->setAuthorization($this->username, $this->password);
        }

        return $this->client;
    }

    /**
     * @param array $returnFields
     * @param null $queryText
     * @param int $offset
     * @param int $hitCount
     * @return SimpleSearchQuery
     */
    public function getSimpleSearchQuery(array $returnFields, $queryText = null, $offset = 0, $hitCount = 5)
    {
        $searchQuery = new SimpleSearchQuery();
        $searchQuery->indexId = $this->account;
        $searchQuery->returnFields = $returnFields;
        if($queryText){
            $searchQuery->queryText = $queryText;
        }

        $searchQuery->language = $this->language;
        $searchQuery->offset = $offset;
        $searchQuery->hitCount = $hitCount;

        return $searchQuery;
    }

    /**
     * @param SimpleSearchQuery $searchQuery
     * @param array|Filter $filter
     */
    protected function addFilter(SimpleSearchQuery $searchQuery, $filter)
    {
        if (!$filter instanceof Filter) {
            $filter = new Filter($filter);
        }
        $searchQuery->filters[] = $filter;
    }

    /**
     * @param SimpleSearchQuery $searchQuery
     * @param array|FacetRequest $facet
     */
    protected function addFacet(SimpleSearchQuery $searchQuery, $facet)
    {
        if (!$facet instanceof FacetRequest) {
            $facet = $this->buildFacetReqeust($facet);
        }
        $searchQuery->facetRequests[] = $facet;
    }

    /**
     * @param array $facet
     * @return FacetRequest
     */
    protected function buildFacetReqeust(array $facet)
    {
        $selectedValues = array();
        if (array_key_exists('values', $facet) && is_array($facet['values'])) {

            if (array_key_exists('start', $facet['values'])) {
                $facetOptions = array(
                    'numerical' => true,
                    'range' => true,
                    'selectedValues' => array(new \com\boxalino\p13n\api\thrift\FacetValue(
                        array('rangeFromInclusive' => $facet['values']['start'],
                            'rangeToExclusive' => $facet['values']['end'])
                    ))
                );
            } else {
                foreach ($facet['values'] as $value) {
                    $selectedValues[] = new \com\boxalino\p13n\api\thrift\FacetValue([
                        'stringValue' => $value
                    ]);
                }
                $facetOptions['selectedValues'] = $selectedValues;
            }
        }
        $facetOptions['fieldName'] = $facet['fieldName'];

        return new FacetRequest($facetOptions);
    }

    /**
     * @param SimpleSearchQuery $searchQuery
     * @param array|SortField $sortField
     */
    public function addSortField(SimpleSearchQuery $searchQuery, $sortField)
    {
        if (!$sortField instanceof SortField) {
            $sortField = $this->buildSortField($sortField);
        }
        $searchQuery->sortFields[] = $sortField;
    }

    /**
     * @param array $sortField
     * @return SortField
     */
    protected function buildSortField(array $sortField)
    {
        return new SortField(array(
            'fieldName' => $sortField['fieldName'],
            'reverse' => array_key_exists('reverse', $sortField) ? $sortField['reverse'] : false
        ));

    }

    /**
     * @param ChoiceRequest $choiceRequest
     * @return \com\boxalino\p13n\api\thrift\ChoiceResponse
     */
    public function choose(ChoiceRequest $choiceRequest)
    {
        return $this->getClient()->choose($choiceRequest);
    }

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
     * @param $choiceResponse
     * @param $facet
     * @return \com\boxalino\p13n\api\thrift\FacetValue[]
     */
    public function extractFacet($choiceResponse, $facet)
    {
        $facets = array();
        /** @var \com\boxalino\p13n\api\thrift\Variant $variant */
        foreach ($choiceResponse->variants as $variant) {
            foreach ($variant->searchResult->facetResponses as $facetResponse) {
                if ($facetResponse->fieldName == $facet) {
                    $facets = array_merge($facets, $facetResponse->values);
                }
            }
        }
        return $facets;
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
     * @param $cemv
     * @param int $offset
     * @param int $hitCount
     * @return AutocompleteResponse
     */
    public function autocomplete(array $returnFields, $queryText, $cemv, $offset = 0, $hitCount = 5)
    {

        // Create main choice request object
        $choiceRequest = $this->getChoiceRequest();
        $autocompleteRequest = new AutocompleteRequest();

        // Setup a search query
        $searchQuery = $this->getSimpleSearchQuery($returnFields, $queryText, $offset, $hitCount);
        $autocompleteQuery = $this->getAutocompleteQuery($queryText, $hitCount);

        // Add inquiry to choice request
        $autocompleteRequest->userRecord = $choiceRequest->userRecord;
        $autocompleteRequest->choiceId = $this->autocompleteWidgetId;
        $autocompleteRequest->profileId = $cemv;
        $autocompleteRequest->autocompleteQuery = $autocompleteQuery;
        $autocompleteRequest->searchChoiceId = $this->searchWidgetId;
        $autocompleteRequest->searchQuery = $searchQuery;

        return $choiceResponse = $this->getClient()->autocomplete($autocompleteRequest);
    }

    /**
     * @param $queryText
     * @param int $hitCount
     * @return AutocompleteQuery
     */
    public function getAutocompleteQuery($queryText, $hitCount = 5)
    {
        $autocompleteQuery = new AutocompleteQuery();
        $autocompleteQuery->indexId = $this->account;;
        $autocompleteQuery->queryText = $queryText;
        $autocompleteQuery->suggestionsHitCount = $hitCount;
        $autocompleteQuery->language = $this->language;
        $autocompleteQuery->highlight = true;
        $autocompleteQuery->highlightPre = $this->highlightPreTag;
        $autocompleteQuery->highlightPost = $this->highlightPostTag;

        return $autocompleteQuery;
    }


    /**
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
     * @return string
     */
    public function getHighlightPreTag()
    {
        return $this->highlightPreTag;
    }

    /**
     * @param string $highlightPreTag
     */
    public function setHighlightPreTag($highlightPreTag)
    {
        $this->highlightPreTag = $highlightPreTag;
    }

    /**
     * @return string
     */
    public function getHighlightPostTag()
    {
        return $this->highlightPostTag;
    }

    /**
     * @param string $highlightPostTag
     */
    public function setHighlightPostTag($highlightPostTag)
    {
        $this->highlightPostTag = $highlightPostTag;
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
     * @param array $returnFields
     * @param $id
     * @param $role
     * @param $choiceIds
     * @param int $offset
     * @param int $hitCount
     * @param string $fieldName
     * @param array $context
     * @return \com\boxalino\p13n\api\thrift\ChoiceResponse
     */
    public function findRawRecommendations(array $returnFields, $id, $role, $choiceIds, $offset = 0, $hitCount = 5,
                                           $fieldName = 'id', $context = array())
    {
        $choiceRequest = $this->getChoiceRequest();
        $choiceRequest->inquiries = array();

        $contextItems = array();

        // Add context parameters if given
        if (count($context)) {
            foreach($context as $key => $value) {
                if (!is_array($value)) {
                    $context[$key] = array($value);
                }
            }
            $requestContext = new RequestContext();
            $requestContext->parameters = $context;
            $choiceRequest->requestContext = $requestContext;
        }

        // Setup a context item
        if (!empty($id)) {
            $contextItems = array(
                new ContextItem(array(
                    'indexId' => $this->account,
                    'fieldName' => $fieldName,
                    'contextItemId' => $id,
                    'role' => $role
                ))
            );
        }

        // Setup a search query
        $searchQuery = $this->getSimpleSearchQuery($returnFields, null, $offset, $hitCount);

        if (!is_array($choiceIds)) {
            $choiceIds = array($choiceIds);
        }
        foreach ($choiceIds as $choiceId) {
            // Setup main choice inquiry object
            $inquiry = new ChoiceInquiry();
            $inquiry->choiceId = $choiceId;
            $inquiry->minHitCount = $hitCount;

            // Connect search query to the inquiry
            $inquiry->simpleSearchQuery = $searchQuery;
            if (!empty($id))$inquiry->contextItems = $contextItems;

            // Add inquiry to choice request
            $choiceRequest->inquiries[] = $inquiry;
        }

        $choiceResponse = $this->choose($choiceRequest);

        return $choiceResponse;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        if($language instanceof RequestStack){
            $language = $language->getCurrentRequest()->getLocale();
        }



        $this->language = substr($language, 0, 2);
    }

}
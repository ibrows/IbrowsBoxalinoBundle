<?php
namespace Ibrows\BoxalinoBundle\Helper;

use com\boxalino\p13n\api\thrift\ChoiceInquiry;
use com\boxalino\p13n\api\thrift\ChoiceRequest;
use com\boxalino\p13n\api\thrift\FacetRequest;
use com\boxalino\p13n\api\thrift\Filter;
use com\boxalino\p13n\api\thrift\SimpleSearchQuery;
use com\boxalino\p13n\api\thrift\SortField;
use Symfony\Component\HttpFoundation\Cookie;
use Thrift\HttpP13n;

/**
 * Class HttpP13nService
 * @package Ibrows\BoxalinoBundle\Client
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class HttpP13nService
{
    /**
     * @var HttpP13n
     */
    protected $client;

    /**
     * @var
     */
    protected $host;

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
    protected $cookieDomain;

    /**
     * @var bool
     */
    protected $relaxationEnabled = true;

    /**
     * HttpP13nService constructor.
     * @param $host
     * @param $account
     * @param $username
     * @param $password
     * @param $cookieDomain
     */
    public function __construct($host, $account, $username, $password, $cookieDomain)
    {
        $this->host = $host;
        $this->account = $account;
        $this->username = $username;
        $this->password = $password;
        $this->cookieDomain = $cookieDomain;
    }

    /**
     * @return Cookie
     */
    public function createCemvCookie()
    {
        return new Cookie('cemv', $this->generateVistorId(), new \DateTime('+3 months'), '/', null, false, false);
    }

    /**
     * @return string
     */
    public function generateVistorId()
    {
        $profileid = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $profileid = bin2hex(openssl_random_pseudo_bytes(16));
        }
        if (empty($profileid)) {
            $profileid = uniqid('', true);
        }

        return $profileid;
    }

    /**
     * @return Cookie
     */
    public function createCemsCookie()
    {
        return new Cookie('cems', $this->generateSessionId(), 0, '/', null, false, false);
    }

    /**
     * @return string
     */
    public function generateSessionId()
    {
        $profileid = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $profileid = bin2hex(openssl_random_pseudo_bytes(24));
        }
        if (empty($profileid)) {
            $profileid = uniqid('', true);
        }

        return $profileid;
    }

    /**
     * @param array $returnFields
     * @param $queryText
     * @param $offset
     * @param $hitCount
     * @param array $filters
     * @param array $facets
     * @param array $sortFields
     * @param string $language
     * @return \com\boxalino\p13n\api\thrift\ChoiceResponse
     */
    public function search(array $returnFields, $queryText, $offset, $hitCount,
                           $filters = array(), $facets = array(), $sortFields = array(), $language = 'en')
    {
        $choiceRequest = $this->getChoiceRequest();
        // Setup main choice inquiry object
        $inquiry = new ChoiceInquiry();
        $inquiry->choiceId = 'search';
        $inquiry->withRelaxation = $this->relaxationEnabled;

        $searchQuery = new SimpleSearchQuery();
        $searchQuery->indexId = $this->account;
        $searchQuery->language = $language;
        $searchQuery->returnFields = $returnFields;
        $searchQuery->offset = $offset;
        $searchQuery->hitCount = $hitCount;
        $searchQuery->queryText = $queryText;

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

        $choiceRequest = $this->getClient()->getChoiceRequest($this->account, $this->cookieDomain);

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
        if(!$sortField instanceof SortField){
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


}
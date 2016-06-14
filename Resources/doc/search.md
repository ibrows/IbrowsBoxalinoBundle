Searching with Boxalino
=======================

When searching the index on Boxalino, it is important to define which fields boxalino should return in the search results.

In general the fields with have the same name as defined in the properties cms, with the prefix "products_", with the exception 
of a few specific fields, for example prices, descriptions and categories (whatever hierarchical structure you associate 
the main index data too).

Here is an example:


```php
    array(
        'title', //name field
        'body', //description field
        'discountedPrice', 
        'standardPrice',
        'categories',
        'products_packet_description',
        'products_bottle_number',
        'products_volume',
        'products_aged_in_barrel',
        'products_article_number',
    );
```

In the following xml snippet you can see that these field types will change the name of the field, and you can only define
one field in the xml to each of these specific types. 

The price fields are a really exception, price => standardPrice and discounted => discountedPrice

```xml

    ...
   <properties>
        <property id="bx_id" type="id">
            <transform>
                <logic source="item_vals" type="direct">
                    <field column="id"/>
                </logic>
            </transform>
            <params/>
        </property>
        <property id="shop_id" type="string">
            <transform>
                <logic source="item_vals" type="direct">
                    <field column="shop_id"/>
                </logic>
            </transform>
            <params/>
        </property>
        <property id="bx_title" type="title">
            <transform>
                <logic source="item_vals" type="direct">
                    <field column="name_de" language="de"/>
                    <field column="name_fr" language="fr"/>
                </logic>
            </transform>
            <params/>
        </property>
        <property id="bx_description" type="body">
            <transform>
                <logic source="item_vals" type="direct">
                    <field column="description_de" language="de"/>
                    <field column="description_fr" language="fr"/>
                </logic>
            </transform>
            <params/>
        </property>
        <property id="bx_listprice" type="price">
            <transform>
                <logic source="item_vals" type="direct">
                    <field column="list_price"/>
                </logic>
            </transform>
            <params/>
        </property>
        <property id="bx_discountedprice" type="discounted">
            <transform>
                <logic source="item_vals" type="direct">
                    <field column="discounted_price"/>
                </logic>
            </transform>
            <params/>
        </property>
        <property id="category" type="hierarchical">
            <transform>
                <logic source="article_category" type="reference">
                    <field column="category_id"/>
                </logic>
            </transform>
            <params>
                <referenceSource value="resource_categories"/>
            </params>
        </property>
        
    ...   
    </properties>                     
```

For more on the [properties xml][1] 

http p13n helper service
------------------------

The Boxalino bundle provides a helper service ```ibrows_boxalino.client.http_p13n_helper``` to help create search,
autocomplete, and suggestion requests to the Boxalino service.

The service simplifies the creation of search requests, the use of filters and facets, and the extraction of results.
Upon instantiation it is given all the credentials needed to access the boxalino server, as well as the current request stack.
This request stack enables it to detect which language is being searched, the domain of the website being viewed, and helps to 
detect filters and facets that are passed in the URL as get parameters.

###Search Requests###

As it is possible to create multiple requests and send them to the boxalino server in one go, there are serveral methods available
to perform the same action.

For example:

```php
    $response = $httpP13nHelper->search($returnFields, $queryText, $offset, $hitCount, $filters, $facets, $sortFields);
```

Is a short form for 

```php
    $request = $httpP13nHelper->createSearchRequest($returnFields, $queryText, $offset, $hitCount, $filters, $facets, $sortFields);
    
    $httpP13nHelper->addRequest($request);
    
    $response = $httpP13nHelper->getResponse();
```

An example of sending multiple request in one single go would be:

```php

    $searchRequest = $helper->createSearchRequest($returnFields, $queryText, $offset, $hitCount, $filters, $facets, $sortFields);
        
    $httpP13nHelper->addRequest($searchRequest);
    
    //returns an array of requests for each context
    $recommendationRequests = $httpP13nHelper->createRawRecommendationsRequests($returnFields, null, $offset, $hitCount, 'id', array('similar'), $filters);
    
    foreach($recommendationRequests as $recommendationRequest){
        $httpP13nHelper->addRequest($recommendationRequest);
    }
    
    $response = $httpP13nHelper->getResponse();
    
    //get the results for the search request
    $searchResults = $httpP13nHelper->extractResults($response, 'search');
    
    //get the results for the recommendation request
    $recommendationResults = $httpP13nHelper->extractResults($response, 'similar');
    
```

In the above case you will now have two sets of results and you will need to retrieve each one separately, using the contexts,
in the above case "search" (default name for the search) and "similar". The available contexts will depend on your boxalino setup.
![recommendation widgets][2] 

###Facets and Filters###

Facets and filters are used to refine the search results. Facets are usually displayed as a list of checkboxes thar the 
user can select multiple values from, e.g. a list of brands, or countries.

Filters are meant to be used more as a programmatic way of restricting the search results, e.g. only online products, or 
only products from a certain category.

The big difference is that when a search is made, boxalino will return the facets that match for a given search term. So
for example you where searching for red wines, the country facets returned by boxalino would only return countries that
are associated with red wines. This in turn allows the user to refine her/his search based on the available facets. It also 
means that the programmer does not need to worry about what countries are available, as boxalino will only return a list 
of countries (in our example here) that make sense.





####Exception to the rules####
Facets: There is one special type of facet, and that is the price (or discountedPrice) which will deliver a range filter. 
We then need to 


Filters: There is one exception to allowing the user to actively select a filter, and that is when the value in the filter is a boolean.
In this case, the programmer needs to actively program the filter, as boxalino will not return a list of available filter 
values, this also makes sense as the filter values would only by 0 or 1.

An example of using a filter in such a way would be when you would want to show only wines that have won an award.






[1]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/blob/master/Resources/doc/properties_xml.md
[2]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/raw/master/Resources/doc/img/recommendation_widgets_boxalino.png


Twig Functions
==============

Boxalino builds its personalisation information by tracking the user, in order to do that, there is some simple javascript
which needs to be added to the page.

There are three simple twig functions to insert the necessary javascript for simple page tracking, and to track searched 
words

The straight forward page view tracker.

``` twig
{{ ibrows_boxalino_tracker() }}
```

The search tracker takes the name of the search GET parameter, plus an array of filter/facet parameters.

``` twig
{{ ibrows_boxalino_search_tracker('search', ['categories', 'products_size']) }}
```

The product view tracker takes the product id (PRODUCTID), or a unique id relating to the product detail page

``` twig
{{ ibrows_boxalino_product_view_tracker('PRODUCTID') }}
```

All of the above trackers incorporate the entire tracking code including ```<script> ``` tags.

If you would just like to retrieve the single line of JS relating to a single promise, you can use the following twig
function. It is important to supply the array of parameters need for the promise to work.

```
<script>
        {{ ibrows_boxalino_get_promise('trackCategoryView', {'categoryId': 1}) }}
</script>
```
The following promises are currently supported, if your promise is not in the list, it will be ignored:

* trackSearch
* trackProductView
* trackAddToBasket
* trackCategoryView
* trackLogin


For more information refer to [boxalino client integration][1] and to the [documentation online][2]


[1]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/raw/master/Resources/doc/files/boxalino_client_integration.pdf
[2]: https://boxalino.zendesk.com/hc/en-gb/articles/203457743-Getting-started-with-the-boxalino-tracking

<?php

/*

This file is for education purposes.  
composer require guzzlehttp/guzzle
composer require duzun/hquery

Here you can learn how to:
- use Guzzle 
- use hQuery (https://github.com/duzun/hQuery.php)
in order to fetch data from the list of url. 

*/

// require './db.class.php' // my own class for adding scraped content to mysql. You'll need to create your own, but i'll show below how to implement database methods with Guzzle

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// setting request options. Headers and proxy
$requestOptions = setRequestOptions($proxy = false);
$client = new Client($requestOptions);
 
// $db = new Db(); // This class is not present in this file. You need to create it if you want to interact with the database.
// i'm using Db() class here with such methods: addItem and addComments just to point where DB requests should be used and how to call them inside Guzzle pool
 
$uriList = file('./urls.txt', FILE_IGNORE_NEW_LINES); // list of urls for scraping

// loading multithreading Guzzle with urls
$requests = function () use ($uriList) {
    foreach ($uriList as $listItem) {
        yield new Request('GET', $listItem);    
    }
};

// fetching and scraping data
$pool = new Pool($client, $requests(), [
    'concurrency' => 3,
    'fulfilled' => function ($response, $index) use ($uriList/*, $db*/) { // notice $db - this is our class Db() 

    $html = (string)$response->getBody();
    $doc = hQuery::fromHTML($html);

    $item['name'] = $doc->find('h1');
    $item['description'] = $doc->find('#about');

    print_r($item);

    // $id = $db->addItem($item); // adding item to the database. addItem() is a method that is not presented in this file, it is here only to show at what point the data is being added to the database

    $reviews = $doc->find('.row mt-3');

    if (!$reviews)
        return;

    $k = 0; // calculator. avoid using "foreach ($reviews as $r => $k) because $reviews is a large multidementional object, $k will have almost random values
    $c = []; // comments
    foreach ($reviews as $r) {
        
        $c[$k] = [
            'text'  => $text = $r->find('q'), // <q>
            'rating' => $r->find('.h1'), // <div class="h1">
            'date' => date("Y-m-d H:i:s")
        ];

        if ($c[$k]['text'] == null)
            continue;
        $c[$k]['text'] = $c[$k]['text']->text();

        if ($c[$k]['rating'] == null)
            continue;
        $c[$k]['rating'] = $c[$k]['rating']->text();


        $k++;
    }

    print_r($c);
    // $db->addComments($c, $id);

    echo '.';  // marking the end of iteration

    // die;

    },
    'rejected' => function ($reason, $index) {
        // this is delivered each failed request
    },
]);

// Initiate the transfers and create a promise
$promise = $pool->promise();

// Force the pool of requests to complete.
$promise->wait();

// ====================

function setRequestOptions($useProxy = false) {

    if ($useProxy) {
        $proxyList = file_get_contents('http://proxyrack.net/rotating/megaproxy/');  
        $proxyArray = explode("\n",trim($proxyList));
        $randomProxy = array_rand(array_flip($proxyArray));
    }

    $chromeVersions = ['44.0.2403.157','60.0.3112.101','62.0.3202.94','51.0.2704.106','64.0.3282.39','68.0.3440.84'];
    $randomChromeVersion = $chromeVersions[rand(0, count($chromeVersions)-1)];

    $requestOptions = [
        'cookies' => new \GuzzleHttp\Cookie\FileCookieJar('./guzzleCookes.txt', true),
        //'proxy' => 'http://dmitry111:qweasdzxc@'.$randomProxy, //.@str_replace(':5055', ':4045', $randomProxy),
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/' . $randomChromeVersion . ' Safari/537.36', 
          'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
          'Accept-Language' => 'en-us,en;q=0.5',
          'Accept-Charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
          'Accept-Encoding' => 'gzip,deflate',
          'Keep-Alive' => '115',
          'Connection' => 'keep-alive',
        ]
    ];

    if ($useProxy)
        $requestOptions['proxy'] = 'http://'.$randomProxy;

    return $requestOptions;
}

 
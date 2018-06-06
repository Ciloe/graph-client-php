<?php

if (empty($argv[1])) {
    throw new \Exception("You need to set your github API key");
}

require_once './../vendor/autoload.php';

$token = $argv[1];

$DS = DIRECTORY_SEPARATOR;
$pool = new \Symfony\Component\Cache\Adapter\FilesystemAdapter();
$fileCache = __DIR__ . $DS . 'Resources' . $DS . 'cache' . $DS . 'cache.php';
$queries = __DIR__ . $DS . 'Resources' . $DS . 'graph' . $DS . 'queries';
$fragments = __DIR__ . $DS . 'Resources' . $DS . 'graph' . $DS . 'fragments';
$adapter = new \Symfony\Component\Cache\Adapter\PhpArrayAdapter($fileCache, $pool);
$queryParser = new \GraphQLClientPhp\Parser\QueryBasicQueryParser();

$cache = new \GraphQLClientPhp\Cache\BasicCache(
    $adapter,
    $queryParser,
    ['queries' => $queries, 'fragments' => $fragments]
);
$cache->warmUp();

$model = new \GraphQLClientPhp\Model\ApiModel('https://api.github.com', 'graphql', $token);
$bridge = new \GraphQLClientPhp\Bridge\BasicBridge($model);
$client = new \GraphQLClientPhp\Client\BasicClient($bridge, $queryParser);


$client
    ->setName('repositories5')
    ->setVariables(['number' => 5])
    ->addQuery($adapter->getItem('repositories')->get());
$client
    ->setName('repositories6')
    ->setVariables(['number' => 6])
    ->addQuery($adapter->getItem('repositories')->get());
$client
    ->setName('repositories10')
    ->setVariables(['number' => 10])
    ->addQuery($adapter->getItem('repositories')->get());

$results = $client->sendQueries(true);

var_dump($results);

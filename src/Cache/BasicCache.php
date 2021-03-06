<?php

namespace GraphQLClientPhp\Cache;

use GraphQLClientPhp\Parser\QueryBasicQueryParser;
use GraphQLClientPhp\Parser\QueryParserInterface;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class BasicCache implements CacheInterface
{
    /**
     * @var PhpArrayAdapter
     */
    private $writer;

    /**
     * @var QueryParserInterface
     */
    private $queryParser;

    /**
     * @var array
     */
    private $files;

    /**
     * @var string
     */
    private $extension;

    /**
     * @var array
     */
    private $queries;

    /**
     * @var array
     */
    private $fragments;

    /**
     * @param PhpArrayAdapter      $writer
     * @param QueryParserInterface $queryParser
     * @param array                $files ['queries' => array, 'fragments' => array]
     * @param string               $extension
     */
    public function __construct(
        PhpArrayAdapter $writer,
        QueryParserInterface $queryParser,
        array $files,
        string $extension = '.graphql'
    ) {
        $this->writer = $writer;
        $this->queryParser = $queryParser;
        $this->files = $files;
        $this->extension = $extension;
        $this->init();
    }

    /**
     * @param string $arrayName
     */
    protected function storedFiles(string $arrayName): void
    {
        $finder = new Finder();
        $templates = $finder->files()->in($this->files[$arrayName]);
        $extension = str_replace('.', '', $this->extension);
        /** @var SplFileInfo $template */
        foreach ($templates as $template) {
            if ($template->getExtension() === $extension) {
                $fragmentName = str_replace(
                    $this->extension,
                    '',
                    $template->getFilename()
                );
                $this->$arrayName[$fragmentName] = $template->getContents();
            }
        }
    }

    public function init(): void
    {
        $this->storedFiles('queries');
        $this->storedFiles('fragments');
    }

    public function warmUp(): void
    {
        $this->queryParser->setFragments($this->fragments);
        $queries = [];

        foreach ($this->queries as $name => $query) {
            $queries[$name] = $this->queryParser->parseQuery($query);
        }
        $queries[CacheInterface::CACHED_FRAGMENT_KEY] = $this->queryParser->getFragments();

        $this->writer->warmUp($queries);
    }

    /**
     * {@inheritdoc}
     */
    public function getWriter(): PhpArrayAdapter
    {
        return $this->writer;
    }

    /**
     * @param string $fileCache
     * @param string $queriesFolder
     * @param string $fragmentsFolder
     *
     * @return CacheInterface
     */
    public static function factory(
        string $fileCache,
        string $queriesFolder,
        string $fragmentsFolder,
        QueryBasicQueryParser $queryParser = null
    ): CacheInterface {
        $pool = new \Symfony\Component\Cache\Adapter\FilesystemAdapter();
        $adapter = new \Symfony\Component\Cache\Adapter\PhpArrayAdapter($fileCache, $pool);

        if (is_null($queryParser)) {
            $queryParser = new \GraphQLClientPhp\Parser\QueryBasicQueryParser();
        }

        $cache = new \GraphQLClientPhp\Cache\BasicCache(
            $adapter,
            $queryParser,
            ['queries' => $queriesFolder, 'fragments' => $fragmentsFolder]
        );
        $cache->warmUp();

        return $cache;
    }
}

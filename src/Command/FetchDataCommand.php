<?php
/**
 * 2019-06-28.
 */

declare(strict_types=1);

namespace App\Command;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class FetchDataCommand.
 */
class FetchDataCommand extends Command
{
    private const SOURCE = 'https://trailers.apple.com/trailers/home/rss/newtrailers.rss';

    /**
     * @var string
     */
    protected static $defaultName = 'fetch:trailers';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $source;

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * FetchDataCommand constructor.
     *
     * @param ClientInterface        $httpClient
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $em
     * @param string|null            $name
     */
    public function __construct(ClientInterface $httpClient, LoggerInterface $logger, EntityManagerInterface $em, string $name = null)
    {
        parent::__construct($name);
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->doctrine = $em;
    }

    public function configure(): void
    {
        $this
            ->setDescription('Fetch data from iTunes Movie Trailers')
            ->addArgument('source', InputArgument::OPTIONAL, 'Overwrite source')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info(sprintf('Start %s at %s', __CLASS__, (string) date_create()->format(DATE_ATOM)));
        $source = self::SOURCE;
        if ($input->getArgument('source')) {
            $source = $input->getArgument('source');
        }

        if (!is_string($source)) {
            throw new RuntimeException('Source must be string');
        }
        $io = new SymfonyStyle($input, $output);
        $io->title(sprintf('Fetch data from %s', $source));

        try {
            $response = $this->httpClient->sendRequest(new Request('GET', $source));
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }
        if (($status = $response->getStatusCode()) !== 200) {
            throw new RuntimeException(sprintf('Response status is %d, expected %d', $status, 200));
        }
        $data = $response->getBody()->getContents();
        $this->processDOM($data);

        $this->logger->info(sprintf('End %s at %s', __CLASS__, (string) date_create()->format(DATE_ATOM)));

        return 0;
    }

    /**
     * @param string $data
     *
     * @throws Exception
     */
    protected function processXml(string $data): void
    {
        $xml = (new \SimpleXMLElement($data))->children();
//        $namespace = $xml->getNamespaces(true)['content'];
//        dd((string) $xml->channel->item[0]->children($namespace)->encoded);

        if (!property_exists($xml, 'channel')) {
            throw new RuntimeException('Could not find \'channel\' element in feed');
        }
        foreach ($xml->channel->item as $item) {
            $trailer = $this->getMovie((string) $item->title)
                ->setTitle((string) $item->title)
                ->setDescription((string) $item->description)
                ->setLink((string) $item->link)
                ->setPubDate($this->parseDate((string) $item->pubDate))
            ;

            $this->doctrine->persist($trailer);
        }

        $this->doctrine->flush();
    }

  /**
   * @param string $data
   *
   * @throws Exception
   */
  protected function processDOM(string $data): void
  {
    $rss = new \DOMDocument();
    $rss->loadXML($data);
    if (!$rss->getElementsByTagName('item')->length) {
      throw new RuntimeException('Could not find \'item\' element in feed');
    }

    $i = 0;
    foreach ($rss->getElementsByTagName('item') as $item) {
      if (++$i >10) {
        break;
      }
      $trailer = $this->getMovie((string) $item->getElementsByTagName('title')->item(0)->nodeValue)
        ->setTitle((string) $item->getElementsByTagName('title')->item(0)->nodeValue)
        ->setDescription((string) $item->getElementsByTagName('description')->item(0)->nodeValue)
        ->setLink((string) $item->getElementsByTagName('link')->item(0)->nodeValue)
        ->setPubDate($this->parseDate((string) $item->getElementsByTagName('pubDate')->item(0)->nodeValue))
        ->setImage((string) $this->getImageSrc($item->getElementsByTagName('encoded')->item(0)->nodeValue))
      ;

      $this->doctrine->persist($trailer);
    }

    $this->doctrine->flush();
  }

  /**
   * @param string $html
   *
   * @return string
   */
  protected function getImageSrc($html): string
  {
    $content = new \DOMDocument();
    $content->loadHTML($html);
    $xpath = new \DOMXPath($content);
    return (string) $xpath->evaluate("string(//img/@src)");
  }


    /**
     * @param string $date
     *
     * @return \DateTime
     *
     * @throws Exception
     */
    protected function parseDate(string $date): \DateTime
    {
        return new \DateTime($date);
    }

    /**
     * @param string $title
     *
     * @return Movie
     */
    protected function getMovie(string $title): Movie
    {
        $item = $this->doctrine->getRepository(Movie::class)->findOneBy(['title' => $title]);

        if ($item === null) {
            $this->logger->info('Create new Movie', ['title' => $title]);
            $item = new Movie();
        } else {
            $this->logger->info('Move found', ['title' => $title]);
        }

        if (!($item instanceof Movie)) {
            throw new RuntimeException('Wrong type!');
        }

        return $item;
    }
}

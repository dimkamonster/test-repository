<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Movie;
use Exception;
use ReflectionClass;
use ReflectionException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Interfaces\RouteCollectorInterface;
use Twig\Environment;

/**
 * Class HomeController.
 */
class HomeController
{
    /**
     * @var RouteCollectorInterface
     */
    private $routeCollector;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * HomeController constructor.
     *
     * @param RouteCollectorInterface $routeCollector
     * @param Environment             $twig
     * @param EntityManagerInterface  $em
     */
    public function __construct(RouteCollectorInterface $routeCollector, Environment $twig, EntityManagerInterface $em)
    {
        $this->routeCollector = $routeCollector;
        $this->twig = $twig;
        $this->em = $em;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     *
     * @throws HttpBadRequestException
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
          $reflection = new ReflectionClass($this);
          $className = $reflection->getShortName();
        } catch (ReflectionException $e) {
          $className = "empty";
        }
        try {
            $data = $this->twig->render('home/index.html.twig', [
              'trailers' => $this->fetchData(),
              'className' => $className,
              'methodName' => __FUNCTION__,
            ]);
        } catch (Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }

        $response->getBody()->write($data);

        return $response;
    }

    /**
     * @return Collection
     */
    protected function fetchData(): Collection
    {
      try {
        $data = $this->em->getRepository(Movie::class)
          ->findAll();

      } catch (Exception $e) {
        $data = [];
      }

        return new ArrayCollection($data);
    }
}

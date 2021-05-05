<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Movie;
use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Interfaces\RouteCollectorInterface;
use Twig\Environment;

/**
 * Class TrailerController.
 */
class TrailerController
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
   * TrailerController constructor.
   *
   * @param RouteCollectorInterface $routeCollector
   * @param Environment $twig
   * @param EntityManagerInterface $em
   */
  public function __construct(RouteCollectorInterface $routeCollector, Environment $twig, EntityManagerInterface $em)
  {
    $this->routeCollector = $routeCollector;
    $this->twig = $twig;
    $this->em = $em;
  }

  /**
   * @param ServerRequestInterface $request
   * @param ResponseInterface $response
   *
   * @return ResponseInterface
   *
   * @throws HttpBadRequestException
   */
  public function trailer(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $id = $request->getAttribute('id');
    if (is_null($id)) {
      throw new HttpBadRequestException($request, 'ID incorrect');
    }

    try {
      $data = $this->twig->render('home/movie.html.twig', [
        'movie' => $this->getMovie(intval($id)),
      ]);
    } catch (Exception $e) {
      throw new HttpBadRequestException($request, $e->getMessage(), $e);
    }

    $response->getBody()->write($data);

    return $response;
  }

  /**
   * @param int $id
   * @return Movie
   * @throws Exception
   */
  protected function getMovie(int $id): Movie
  {
    /** @var Movie $movie */
    $movie = $this->em->getRepository(Movie::class)
      ->findOneBy(['id' => intval($id)]);
    if (is_null($movie)) {
      throw new Exception('empty');
    }
    return $movie;
  }

}

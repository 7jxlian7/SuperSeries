<?php

namespace App\Controller;

use App\Entity\ExternalRating;
use App\Entity\Series;
use App\Form\SeriesType;
use App\Repository\SeriesRepository;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/series')]
class SeriesController extends AbstractController
{

    #[Route('/{numPage}', name: 'series_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, SeriesRepository $seriesRepo, $numPage = 1): Response
    {
        $series = $seriesRepo->getSeries($numPage, 20);
        
        $ratings = $entityManager
            ->getRepository(ExternalRating::class)
            ->findAll();

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'ratings' => $ratings,
            'num_page' => $numPage
        ]);
    }

    #[Route('/new', name: 'series_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $series = new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/new.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/view/{id}', name: 'series_show', methods: ['GET'])]
    public function show(EntityManagerInterface $entityManager, Series $series): Response
    {
        $rating = $entityManager
            ->getRepository(ExternalRating::class)
            ->findBy(['series' => $series]);
            
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'rating' => $rating[0]
        ]);
    }

    #[Route('/{id}/edit', name: 'series_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/edit.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'series_delete', methods: ['POST'])]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/poster/{id}', name: 'series_poster')]
    public function getPoster(Series $serie): Response
    {
        $poster = $serie->getPoster();
        $headers = array(
            'Content-Type'     => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$poster.'"');
        return new Response(stream_get_contents($poster, -1, 0), 200, $headers);
    }
}
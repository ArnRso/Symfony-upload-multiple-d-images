<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/article")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/", name="article_index", methods={"GET"})
     */
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="article_new", methods={"GET","POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // stock toutes les images envoyées par le formulaire dans $images
            $images = $request->files->get('article')['images'];

            // boucle sur chacune des images
            foreach ($images as $image) {
                // stock le dossier de destination défini dans config/services.yaml
                $upload_directory = $this->getParameter('uploads_directory');
                // génère un nom unique pour chauque photo
                $filename = md5(uniqid()) . '.' . $image->guessExtension();
                // déplace le fichier dans le dossier désiré
                $image->move(
                // dossier de destination
                    $upload_directory,
                    // nom du fichier
                    $filename
                );
                // Ajoute le nom du fichier image à l'array Images dans la BDD
                $article->addImages($filename);
            }

            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="article_show", methods={"GET"})
     */
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="article_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }


    /**
     * Permet de supprimer une image en particulier d'un article donné
     * @Route("/{id}/img/delete/{id_image}", name="article_image_delete")
     */
    public function deleteImage($id, $id_image, EntityManagerInterface $entityManager, Request $request, ArticleRepository $articleRepository)
    {
        $article = $articleRepository->find($id);
        $images = $article->getImages();
        array_splice($images, $id_image, 1);
        $article->setImages($images);
        $entityManager->persist($article);
        $entityManager->flush();
        return $this->redirectToRoute('article_edit', ['id'=>$id]);

    }


    /**
     * @Route("/{id}", name="article_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index');
    }
}

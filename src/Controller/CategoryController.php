<?php

namespace App\Controller;

use App\Dto\categoryCountPostsDTO;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CategoryController extends AbstractController
{
    private CategoryRepository $categoryRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CategoryRepository $categoryRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }


    #[Route('/api/categories', name: 'app_categories')]
    public function getAllCategories(): Response
    {
        $categories = $this->categoryRepository->findAll();

        $categoriesJson = $this->serializer->serialize($categories,'json',['groups' => 'get_post']);

        return new Response($categoriesJson,Response::HTTP_OK,['content-type' => 'application/json']);
    }

    #[Route('/api/categories/{id}/posts', name: 'app_categories_id_posts')]
    public function getPostsByCatId(int $id): Response
    {
        $categorie = $this->categoryRepository->findOneBy(['id'=>$id]);

        $categoriesJson = $this->serializer->serialize($categorie,'json',['groups' => 'get_posts_by_cat']);

        return new Response($categoriesJson,Response::HTTP_OK,['content-type' => 'application/json']);
    }

    #[Route('/api/categories/{id}', name: 'app_categories_id')]
    public function getCatById(int $id): Response
    {
        $categorie = $this->categoryRepository->findOneBy(['id'=>$id]);
        if (!$categorie){
            return $this->generateError("La catégorie $id demandé n'existe pas",404);
        }
        $catDto = new categoryCountPostsDTO();
        $catDto->setId($categorie->getId());
        $catDto->setTitle($categorie->getTitle());
        $catDto->setNbPosts(count($categorie->getPosts()));

        $categoriesJson = $this->serializer->serialize($catDto,'json');

        return new Response($categoriesJson,Response::HTTP_OK,['content-type' => 'application/json']);
    }

    private function generateError(string $message, int $status) : Response
    {
        $erreur = [
            'status' => $status,
            'message' => $message
        ];
        // Renvoyer la réponse au format json (erreur)
        return new Response(json_encode($erreur),$status,['content-type' => 'application/json']);
    }
}

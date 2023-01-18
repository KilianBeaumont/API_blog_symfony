<?php

namespace App\Controller;

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

        foreach ($categories as $categorie) {
            $nbPosts = count($categorie->getPosts());

        }

        $categoriesJson = $this->serializer->serialize($categories,'json',['groups' => 'get_post']);


        return new Response($categoriesJson,Response::HTTP_OK,['content-type' => 'application/json']);
    }

    #[Route('/api/categories/{id}/posts', name: 'app_categories_id')]
    public function getPostsByCatId(int $id): Response
    {
        $categorie = $this->categoryRepository->findOneBy(['id'=>$id]);

        $categoriesJson = $this->serializer->serialize($categorie,'json',['groups' => 'get_posts_by_cat']);

        return new Response($categoriesJson,Response::HTTP_OK,['content-type' => 'application/json']);
    }
}

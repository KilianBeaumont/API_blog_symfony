<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use MongoDB\Driver\ServerApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostController extends AbstractController
{

    private PostRepository $postRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    /**
     * @param PostRepository $postRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     */
    public function __construct(PostRepository $postRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->postRepository = $postRepository;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/api/posts', name: 'api_getPosts', methods: ['GET'])]
    public function getPosts(): Response
    {
        // Rechercher les postes dans la base de données
        $posts = $this->postRepository->findAll();
        // Normaliser le tableau $posts
        // Transformer $posts en un tableau associatif
        //$postsArray = $normalizer->normalize($posts);
        // Encoder en JSON
        //$postsJson = json_encode($postsArray);

        // Serializer le tableau $posts en JSON
        $postsJson = $this->serializer->serialize($posts,'json',[
            'groups' => 'list_posts'
        ]);

        // Générer la réponse HTTP
        //$response = new Response();
        //$response->setStatusCode(Response::HTTP_OK);
        //$response->headers->set('content-type','application/json');
        //$response->setContent($postsJson);
        //return $response;

        return new Response($postsJson,Response::HTTP_OK,['content-type' => 'application/json']);
    }

    #[Route('/api/posts/{id}', name: 'api_getPosts_id',methods: ['GET'])]
    public function getPosts_id(int $id): Response
    {
        // Rechercher les postes dans la base de données
        $posts = $this->postRepository->findOneBy(['id'=> $id]);
        // Générer une erreur si le post recherché n'existe pas
        if (!$posts){
            return $this->generateError("Le post demandé n'existe pas",404);
        }
        $postsJson = $this->serializer->serialize($posts,'json',[
            'groups' => 'get_post'
        ]);

        return new Response($postsJson,Response::HTTP_OK,['content-type' => 'application/json']);
    }

    #[Route('/api/posts', name: 'api_createPost', methods: ['POST'])]
    public function createPost(Request $request) : Response
    {
        // Récupérer dans la requête le body contenant
        // le JSON du nouveau post
        $bodyRequest = $request->getContent();
        // Déserializer le JSON en un objet de la classe Post
        try {
            // Surveiller si le code ci-dessous lève une exception
            $post = $this->serializer->deserialize($bodyRequest,Post::class, 'json');
        }
        catch (NotEncodableValueException $exception) {
            return $this->generateError("La requête n'est pas valide.",Response::HTTP_BAD_REQUEST);
        }

        // Validations des données en fonction des règles de validations définies
        $erreurs = $this->validator->validate($post);
        // Tester s'il y a des erreurs
        if (count($erreurs) > 0){
            // Transforme le tableau en JSON
            $erreursJson = $this->serializer->serialize($erreurs,'json');
            return new Response($erreursJson,Response::HTTP_BAD_REQUEST,['content-type' => 'application/json']);
        }

        // Insérer le nouveau post dans la base de données
        $post->setCreatedAt(New \DateTime());
        $this->entityManager->persist($post); // Créer le INSERT
        $this->entityManager->flush($post);
        // Générer la réponse HTTP
        // serializer $post en json
        $postJson = $this->serializer->serialize($post,'json');
        return new Response($postJson,Response::HTTP_CREATED,['content-type' => 'application/json']);
    }

    #[Route('/api/posts/{id}', name: 'api_deletePost', methods: ['DELETE'])]
    public function deletePost(int $id) : Response
    {
        $post = $this->postRepository->findOneBy(['id'=> $id]);
        if (!$post){
            return $this->generateError("Le post à supprimer n'existe pas",404);
        }
        $this->entityManager->remove($post);
        $this->entityManager->flush();
        return new Response(null,Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/posts/{id}', name: 'api_updatePost', methods: ['PUT'])]
    public function updatePost(int $id, Request $request) : Response
    {
        // Récupérer le body de la requête
        $bodyRequest = $request->getContent();
        // Récupérer dans la base de données le post à modifier
        $post = $this->postRepository->findOneBy(['id'=> $id]);
        if (!$post){
            return $this->generateError("Le post à modidier n'existe pas",404);
        }
        // Modifier le post avec les données du body (JSON)
        try {
            $this->serializer->deserialize($bodyRequest, Post::class,'json',
                ['object_to_populate' => $post]);
        }
        catch (NotEncodableValueException $exception) {
            return $this->generateError("La requête n'est pas valide.",Response::HTTP_BAD_REQUEST);
        }

        // Modifier le post dans la base de données
        $this->entityManager->flush();
        return new Response(null,Response::HTTP_NO_CONTENT);
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

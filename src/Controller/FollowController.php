<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Follow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\FunctionU\MyFunction;
use App\Response\CustomJsonResponse;

class FollowController extends AbstractController
{
    private $em;
    private $myFunction;

    public function __construct(
        EntityManagerInterface $em,
        MyFunction $myFunction
    ) {
        $this->em = $em;
        $this->myFunction = $myFunction;
    }

    /**
     * @Route("/follow", name="createFollow", methods={"POST"})
     */
    public function createFollow(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $follower = $this->myFunction->requestUser($request);
        $followingId = $data['following_id'] ?? null;

        if (!$followingId) {
            return new CustomJsonResponse(null, 400, 'L\'ID de l\'utilisateur à suivre est requis');
        }

        $following = $this->em->getRepository(User::class)->find($followingId);

        if (!$following) {
            return new CustomJsonResponse(null, 203, 'L\'utilisateur à suivre n\'existe pas');
        }

        if ($follower === $following) {
            return new CustomJsonResponse(null, 400, 'Vous ne pouvez pas vous suivre vous-même');
        }

        $existingFollow = $this->em->getRepository(Follow::class)->findOneBy([
            'follower' => $follower,
            'following' => $following
        ]);

        if ($existingFollow) {
            return new CustomJsonResponse(null, 400, 'Vous suivez déjà cet utilisateur');
        }

        $follow = new Follow();
        $follow->setFollower($follower);
        $follow->setFollowing($following);

        $this->em->persist($follow);
        $this->em->flush();

        return new CustomJsonResponse(null, 201, 'Vous suivez maintenant cet utilisateur');
    }

    /**
     * @Route("/follow/{id}", name="deleteFollow", methods={"DELETE"})
     */
    public function deleteFollow(Request $request, int $id): JsonResponse
    {
        $following = $this->myFunction->requestUser($request);
        $follow = $this->em->getRepository(Follow::class)->findOneBy([
            'following' => $following,
            'follower' => $id
        ]);



        if (!$follow) {
            return new CustomJsonResponse(null, 203, 'Relation de suivi non trouvée');
        }

        $this->em->remove($follow);
        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Vous ne suivez plus cet utilisateur');
    }

    /**
     * @Route("/followers", name="getFollowers", methods={"GET"})
     */
    public function getFollowers(Request $request): JsonResponse
    {
        $user = $this->myFunction->requestUser($request);
        $followers = $this->em->getRepository(Follow::class)->findBy(['following' => $user]);

        $formattedFollowers = array_map(function ($follow) {
            return $this->myFunction->formatUser($follow->getFollower());
        }, $followers);

        return new CustomJsonResponse(['followers' => $formattedFollowers], 200, 'Liste des abonnés récupérée avec succès');
    }

    /**
     * @Route("/following", name="getFollowing", methods={"GET"})
     */
    public function getFollowing(Request $request): JsonResponse
    {
        $user = $this->myFunction->requestUser($request);
        $following = $this->em->getRepository(Follow::class)->findBy(['follower' => $user]);

        $formattedFollowing = array_map(function ($follow) {
            return $this->myFunction->formatUser($follow->getFollowing());
        }, $following);

        return new CustomJsonResponse(['following' => $formattedFollowing], 200, 'Liste des abonnements récupérée avec succès');
    }

    /**
     * @Route("/follow/check/{id}", name="checkFollow", methods={"GET"})
     */
    public function checkFollow(Request $request, int $id): JsonResponse
    {
        $follower = $this->myFunction->requestUser($request);
        $following = $this->em->getRepository(User::class)->find($id);

        if (!$following) {
            return new CustomJsonResponse(null, 203, 'L\'utilisateur n\'existe pas');
        }

        $follow = $this->em->getRepository(Follow::class)->findOneBy([
            'follower' => $follower,
            'following' => $following
        ]);

        return new CustomJsonResponse(['isFollowing' => (bool)$follow], 200, 'Statut de suivi vérifié avec succès');
    }
}

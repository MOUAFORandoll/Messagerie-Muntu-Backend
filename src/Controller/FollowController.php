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
use Doctrine\ORM\Tools\Pagination\Paginator;

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
     * @Route("/new-contact", name="createFollowNewContact", methods={"POST"})
     */
    public function createFollowNewContact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $follower = $this->myFunction->requestUser($request);
        $nameContact = $data['nameContact'] ?? null;
        $sunameContact = $data['sunameContact'] ?? null;
        if (!$nameContact || !$sunameContact) {
            return new CustomJsonResponse(null, 400, 'Le nom et le prénom du contact sont requis');
        }
        $codePhoneContact = $data['codePhoneContact'] ?? null;
        $phoneContact = $data['phoneContact'] ?? null;
        if (!$codePhoneContact || !$phoneContact) {
            return new CustomJsonResponse(null, 400, 'le numéro de téléphone du contact sont requis');
        }

        $following = $this->em->getRepository(User::class)->findOneBy(['phone' => $phoneContact, 'codePhone' => $codePhoneContact]);
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
        $follow->setNameContact($nameContact);
        $follow->setSunameContact($sunameContact);

        $this->em->persist($follow);
        $this->em->flush();

        return new CustomJsonResponse(null, 201, 'Contact ajouté avec succès');
    }
    /**
     * @Route("/follow/contacts", name="getContacts", methods={"GET"})
     */
    public function getContacts(Request $request): JsonResponse
    {
        $user = $this->myFunction->requestUser($request);
        if (!$user) {
            return new CustomJsonResponse(null, 400, 'Utilisateur non trouvé');
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $contactsQuery = $this->em->getRepository(Follow::class)->createQueryBuilder('f')
            ->where('f.follower = :user')
            ->setParameter('user', $user)
            ->getQuery();

        $paginator = new Paginator($contactsQuery);
        $totalContacts = count($paginator);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $formattedContacts = array_map(function ($follow) {
            return [
                'id' => $follow->getFollowing()->getId(),
                'username' => $follow->getFollowing()->getUsername(),
                'nameContact' => $follow->getNameContact(),
                'sunameContact' => $follow->getSunameContact(),
                'phone' => $follow->getFollowing()->getPhone(),
                'codePhone' => $follow->getFollowing()->getCodePhone()
            ];
        }, iterator_to_array($paginator));

        $paginatedResults = new \stdClass();
        $paginatedResults->total = $totalContacts;
        $paginatedResults->currentPage = $page;
        $paginatedResults->items = $formattedContacts;

        return new   CustomJsonResponse([
            'total' => $paginatedResults->total,
            'page' => $paginatedResults->currentPage,
            'data' => $paginatedResults->items
        ], 200, 'Historique récupéré avec succès');
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
        $follow->setNameContact($following->getUsername());
        $follow->setSunameContact($following->getSurname());
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

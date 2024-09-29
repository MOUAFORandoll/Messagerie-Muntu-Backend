<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Symfony\Component\Routing\Annotation\Route;
use App\FunctionU\MyFunction;
use App\Entity\User;
use App\Entity\Groupe;
use App\Entity\GroupeUser;
use App\Entity\TypeParticipant;

class GroupeController extends AbstractController
{
    private $em;
    private $serializer;
    private $clientWeb;
    private $myFunction;

    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        HttpClientInterface $clientWeb,
        MyFunction $myFunction
    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->myFunction = $myFunction;
        $this->clientWeb = $clientWeb;
    }

    /**
     * @Route("/groupe/create", name="createGroupe", methods={"POST"})
     */
    public function createGroupe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['libelle']) || !isset($data['description']) || !isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $groupe = new Groupe();
        $groupe->setLibelle($data['libelle']);
        $groupe->setDescription($data['description']);

        $groupeUser = new GroupeUser();
        $groupeUser->setGroupe($groupe);
        $groupeUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(1);
        if (!$typeUser) {
            return new JsonResponse(['message' => 'Type utilisateur non trouvé'], 404);
        }
        $groupeUser->setTypeParticipant($typeUser);

        $this->em->persist($groupe);
        $this->em->persist($groupeUser);
        $this->em->flush();

        return new JsonResponse(['message' => 'Groupe créé avec succès', 'groupeId' => $groupe->getId()], 201);
    }

    /**
     * @Route("/groupe/{groupeId}/set-admin", name="setGroupeAdmin", methods={"POST"})
     */
    public function setGroupeAdmin(Request $request, $groupeId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new JsonResponse(['message' => 'Groupe non trouvé'], 404);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $groupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if (!$groupeUser) {
            return new JsonResponse(['message' => 'L\'utilisateur n\'est pas membre de ce groupe'], 400);
        }

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(2);
        if (!$typeUser) {
            return new JsonResponse(['message' => 'Type utilisateur non trouvé'], 404);
        }
        $groupeUser->setTypeParticipant($typeUser);

        $this->em->flush();

        return new JsonResponse(['message' => 'Administrateur défini avec succès'], 200);
    }

    /**
     * @Route("/groupe/{groupeId}/join", name="joinGroupe", methods={"POST"})
     */
    public function joinGroupe(Request $request, $groupeId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new JsonResponse(['message' => 'Groupe non trouvé'], 404);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $existingGroupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if ($existingGroupeUser) {
            return new JsonResponse(['message' => 'L\'utilisateur est déjà membre de ce groupe'], 400);
        }

        $groupeUser = new GroupeUser();
        $groupeUser->setGroupe($groupe);
        $groupeUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(3);
        if (!$typeUser) {
            return new JsonResponse(['message' => 'Type utilisateur non trouvé'], 404);
        }
        $groupeUser->setTypeParticipant($typeUser);

        $this->em->persist($groupeUser);
        $this->em->flush();

        return new JsonResponse(['message' => 'Utilisateur ajouté au groupe avec succès'], 201);
    }

    /**
     * @Route("/groupe/{groupeId}/leave", name="leaveGroupe", methods={"POST"})
     */
    public function leaveGroupe(Request $request, $groupeId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new JsonResponse(['message' => 'Groupe non trouvé'], 404);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $groupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if (!$groupeUser) {
            return new JsonResponse(['message' => 'L\'utilisateur n\'est pas membre de ce groupe'], 400);
        }

        $this->em->remove($groupeUser);
        $this->em->flush();

        return new JsonResponse(['message' => 'Utilisateur retiré du groupe avec succès'], 200);
    }

    /**
     * @Route("/groupe/{groupeId}/kick", name="kickFromGroupe", methods={"POST"})
     */
    public function kickFromGroupe(Request $request, $groupeId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId']) || !isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new JsonResponse(['message' => 'Groupe non trouvé'], 404);
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new JsonResponse(['message' => 'Administrateur non trouvé'], 404);
        }

        $adminGroupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $admin,
            'typeUser' => 2
        ]);

        if (!$adminGroupeUser) {
            return new JsonResponse(['message' => 'Vous n\'avez pas les droits d\'administrateur pour ce groupe'], 403);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur à expulser non trouvé'], 404);
        }

        $groupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if (!$groupeUser) {
            return new JsonResponse(['message' => 'L\'utilisateur n\'est pas membre de ce groupe'], 400);
        }

        $this->em->remove($groupeUser);
        $this->em->flush();

        return new JsonResponse(['message' => 'Utilisateur expulsé du groupe avec succès'], 200);
    }

    /**
     * @Route("/groupe/{groupeId}/update", name="updateGroupe", methods={"PUT"})
     */
    public function updateGroupe(Request $request, $groupeId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new JsonResponse(['message' => 'Groupe non trouvé'], 404);
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new JsonResponse(['message' => 'Administrateur non trouvé'], 404);
        }

        $adminGroupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $admin,
            'typeUser' => 2
        ]);

        if (!$adminGroupeUser) {
            return new JsonResponse(['message' => 'Vous n\'avez pas les droits d\'administrateur pour ce groupe'], 403);
        }

        if (isset($data['libelle'])) {
            $groupe->setLibelle($data['libelle']);
        }

        if (isset($data['description'])) {
            $groupe->setDescription($data['description']);
        }

        $this->em->flush();

        return new JsonResponse(['message' => 'Informations du groupe mises à jour avec succès'], 200);
    }

    /**
     * @Route("/groupe/{groupeId}/membres", name="listeMembresGroupe", methods={"GET"})
     */
    public function listeMembresGroupe($groupeId): JsonResponse
    {
        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new JsonResponse(['message' => 'Groupe non trouvé'], 404);
        }

        $membres = $this->em->getRepository(GroupeUser::class)->findBy(['groupe' => $groupe]);

        $membresData = [];
        foreach ($membres as $membre) {
            $membresData[] = [
                'id' => $membre->getMuntu()->getId(),
                'username' => $membre->getMuntu()->getUsername(),

                'typeUser' => $membre->getTypeParticipant()->getLibelle()
            ];
        }

        return new JsonResponse(['membres' => $membresData], 200);
    }
}

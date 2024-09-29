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
use App\Entity\Message;
use App\Entity\MessageObject;
use App\Entity\Canal;
use App\Entity\CanalUser;
use App\Entity\TypeObject;
use App\Entity\TypeParticipant;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class CanalController extends AbstractController
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
     * @Route("/canal/create", name="createCanal", methods={"POST"})
     */
    public function createCanal(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['libelle']) || !isset($data['description']) || !isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $canal = new Canal();
        $canal->setLibelle($data['libelle']);
        $canal->setDescription($data['description']);

        $canalUser = new CanalUser();
        $canalUser->setCanal($canal);
        $canalUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(1);
        if (!$typeUser) {
            return new JsonResponse(['message' => 'Type utilisateur non trouvé'], 404);
        }
        $canalUser->setTypeParticipant($typeUser);

        $this->em->persist($canal);
        $this->em->persist($canalUser);
        $this->em->flush();

        return new JsonResponse(['message' => 'Canal créé avec succès', 'canalId' => $canal->getId()], 201);
    }

    /**
     * @Route("/canal/{canalId}/set-admin", name="setCanalAdmin", methods={"POST"})
     */
    public function setCanalAdmin(Request $request, $canalId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new JsonResponse(['message' => 'Canal non trouvé'], 404);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $canalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $user
        ]);

        if (!$canalUser) {
            return new JsonResponse(['message' => 'L\'utilisateur n\'est pas membre de ce canal'], 400);
        }

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(2);
        if (!$typeUser) {
            return new JsonResponse(['message' => 'Type utilisateur non trouvé'], 404);
        }
        $canalUser->setTypeParticipant($typeUser);

        $this->em->flush();

        return new JsonResponse(['message' => 'Administrateur défini avec succès'], 200);
    }

    /**
     * @Route("/canal/{canalId}/join", name="joinCanal", methods={"POST"})
     */
    public function joinCanal(Request $request, $canalId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new JsonResponse(['message' => 'Canal non trouvé'], 404);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $existingCanalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $user
        ]);

        if ($existingCanalUser) {
            return new JsonResponse(['message' => 'L\'utilisateur est déjà membre de ce canal'], 400);
        }

        $canalUser = new CanalUser();
        $canalUser->setCanal($canal);
        $canalUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(3);
        if (!$typeUser) {
            return new JsonResponse(['message' => 'Type utilisateur non trouvé'], 404);
        }
        $canalUser->setTypeParticipant($typeUser);

        $this->em->persist($canalUser);
        $this->em->flush();

        return new JsonResponse(['message' => 'Utilisateur ajouté au canal avec succès'], 201);
    }

    /**
     * @Route("/canal/{canalId}/leave", name="leaveCanal", methods={"POST"})
     */
    public function leaveCanal(Request $request, $canalId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new JsonResponse(['message' => 'Canal non trouvé'], 404);
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $canalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $user
        ]);
        if (!$canalUser) {
            return new JsonResponse(['message' => 'L\'utilisateur n\'est pas membre de ce canal'], 400);
        }
        $canalUser->setDeletedAt();

        $this->em->remove($canalUser);
        $this->em->flush();

        return new JsonResponse(['message' => 'Utilisateur retiré du canal avec succès'], 200);
    }

    /**
     * @Route("/canal/{canalId}/kick", name="kickUserFromCanal", methods={"POST"})
     */
    public function kickUserFromCanal(Request $request, $canalId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId']) || !isset($data['userIdToKick'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new JsonResponse(['message' => 'Canal non trouvé'], 404);
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new JsonResponse(['message' => 'Administrateur non trouvé'], 404);
        }

        $adminCanalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $admin,
            'typeUser' => $this->em->getRepository(TypeParticipant::class)->find(2)
        ]);

        if (!$adminCanalUser) {
            return new JsonResponse(['message' => 'Vous n\'avez pas les droits d\'administrateur pour ce canal'], 403);
        }

        $userToKick = $this->em->getRepository(User::class)->find($data['userIdToKick']);
        if (!$userToKick) {
            return new JsonResponse(['message' => 'Utilisateur à expulser non trouvé'], 404);
        }

        $canalUserToKick = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $userToKick
        ]);

        if (!$canalUserToKick) {
            return new JsonResponse(['message' => 'L\'utilisateur n\'est pas membre de ce canal'], 400);
        }

        $this->em->remove($canalUserToKick);
        $this->em->flush();

        return new JsonResponse(['message' => 'Utilisateur expulsé du canal avec succès'], 200);
    }

    /**
     * @Route("/canal/{canalId}/update", name="updateCanal", methods={"PUT"})
     */
    public function updateCanal(Request $request, $canalId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId'])) {
            return new JsonResponse(['message' => 'Données manquantes'], 400);
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new JsonResponse(['message' => 'Canal non trouvé'], 404);
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new JsonResponse(['message' => 'Administrateur non trouvé'], 404);
        }

        $adminCanalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $admin,
            'typeUser' => $this->em->getRepository(TypeParticipant::class)->find(2)
        ]);

        if (!$adminCanalUser) {
            return new JsonResponse(['message' => 'Vous n\'avez pas les droits d\'administrateur pour ce canal'], 403);
        }

        if (isset($data['libelle'])) {
            $canal->setLibelle($data['libelle']);
        }

        if (isset($data['description'])) {
            $canal->setDescription($data['description']);
        }

        $this->em->flush();

        return new JsonResponse(['message' => 'Informations du canal mises à jour avec succès'], 200);
    }

    /**
     * @Route("/canal/{canalId}/membres", name="listeMembresCanal", methods={"GET"})
     */
    public function listeMembresCanal($canalId): JsonResponse
    {
        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new JsonResponse(['message' => 'Canal non trouvé'], 404);
        }

        $membres = $this->em->getRepository(CanalUser::class)->findBy(['canal' => $canal]);

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

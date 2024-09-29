<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
use App\Response\CustomJsonResponse;

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
    public function createCanal(Request $request): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['libelle']) || !isset($data['description']) || !isset($data['userId'])) {
            return new CustomJsonResponse(null, 400, 'Données manquantes');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 404, 'Utilisateur non trouvé');
        }

        $canal = new Canal();
        $canal->setLibelle($data['libelle']);
        $canal->setDescription($data['description']);

        $canalUser = new CanalUser();
        $canalUser->setCanal($canal);
        $canalUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(1);
        if (!$typeUser) {
            return new CustomJsonResponse(null, 404, 'Type utilisateur non trouvé');
        }
        $canalUser->setTypeParticipant($typeUser);

        $this->em->persist($canal);
        $this->em->persist($canalUser);
        $this->em->flush();

        return new CustomJsonResponse(['canalId' => $canal->getId()], 201, 'Canal créé avec succès');
    }

    /**
     * @Route("/canal/{canalId}/set-admin", name="setCanalAdmin", methods={"POST"})
     */
    public function setCanalAdmin(Request $request, $canalId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new CustomJsonResponse(null, 400, 'Données manquantes');
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new CustomJsonResponse(null, 404, 'Canal non trouvé');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 404, 'Utilisateur non trouvé');
        }

        $canalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $user
        ]);

        if (!$canalUser) {
            return new CustomJsonResponse(null, 400, 'L\'utilisateur n\'est pas membre de ce canal');
        }

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(2);
        if (!$typeUser) {
            return new CustomJsonResponse(null, 404, 'Type utilisateur non trouvé');
        }
        $canalUser->setTypeParticipant($typeUser);

        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Administrateur défini avec succès');
    }

    /**
     * @Route("/canal/{canalId}/join", name="joinCanal", methods={"POST"})
     */
    public function joinCanal(Request $request, $canalId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new CustomJsonResponse(null, 400, 'Données manquantes');
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new CustomJsonResponse(null, 404, 'Canal non trouvé');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 404, 'Utilisateur non trouvé');
        }

        $existingCanalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $user
        ]);

        if ($existingCanalUser) {
            return new CustomJsonResponse(null, 400, 'L\'utilisateur est déjà membre de ce canal');
        }

        $canalUser = new CanalUser();
        $canalUser->setCanal($canal);
        $canalUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(3);
        if (!$typeUser) {
            return new CustomJsonResponse(null, 404, 'Type utilisateur non trouvé');
        }
        $canalUser->setTypeParticipant($typeUser);

        $this->em->persist($canalUser);
        $this->em->flush();

        return new CustomJsonResponse(null, 201, 'Utilisateur ajouté au canal avec succès');
    }

    /**
     * @Route("/canal/{canalId}/leave", name="leaveCanal", methods={"POST"})
     */
    public function leaveCanal(Request $request, $canalId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new CustomJsonResponse(null, 400, 'Données manquantes');
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new CustomJsonResponse(null, 404, 'Canal non trouvé');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 404, 'Utilisateur non trouvé');
        }

        $canalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $user
        ]);
        if (!$canalUser) {
            return new CustomJsonResponse(null, 400, 'L\'utilisateur n\'est pas membre de ce canal');
        }
        $canalUser->setDeletedAt();

        $this->em->remove($canalUser);
        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Utilisateur retiré du canal avec succès');
    }

    /**
     * @Route("/canal/{canalId}/kick", name="kickUserFromCanal", methods={"POST"})
     */
    public function kickUserFromCanal(Request $request, $canalId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId']) || !isset($data['userIdToKick'])) {
            return new CustomJsonResponse(null, 400, 'Données manquantes');
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new CustomJsonResponse(null, 404, 'Canal non trouvé');
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new CustomJsonResponse(null, 404, 'Administrateur non trouvé');
        }

        $adminCanalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $admin,
            'typeUser' => $this->em->getRepository(TypeParticipant::class)->find(2)
        ]);

        if (!$adminCanalUser) {
            return new CustomJsonResponse(null, 403, 'Vous n\'avez pas les droits d\'administrateur pour ce canal');
        }

        $userToKick = $this->em->getRepository(User::class)->find($data['userIdToKick']);
        if (!$userToKick) {
            return new CustomJsonResponse(null, 404, 'Utilisateur à expulser non trouvé');
        }

        $canalUserToKick = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $userToKick
        ]);

        if (!$canalUserToKick) {
            return new CustomJsonResponse(null, 400, 'L\'utilisateur n\'est pas membre de ce canal');
        }

        $this->em->remove($canalUserToKick);
        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Utilisateur expulsé du canal avec succès');
    }

    /**
     * @Route("/canal/{canalId}/update", name="updateCanal", methods={"PUT"})
     */
    public function updateCanal(Request $request, $canalId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId'])) {
            return new CustomJsonResponse(null, 400, 'Données manquantes');
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new CustomJsonResponse(null, 404, 'Canal non trouvé');
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new CustomJsonResponse(null, 404, 'Administrateur non trouvé');
        }

        $adminCanalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'canal' => $canal,
            'muntu' => $admin,
            'typeUser' => $this->em->getRepository(TypeParticipant::class)->find(2)
        ]);

        if (!$adminCanalUser) {
            return new CustomJsonResponse(null, 403, 'Vous n\'avez pas les droits d\'administrateur pour ce canal');
        }

        if (isset($data['libelle'])) {
            $canal->setLibelle($data['libelle']);
        }

        if (isset($data['description'])) {
            $canal->setDescription($data['description']);
        }

        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Informations du canal mises à jour avec succès');
    }

    /**
     * @Route("/canal/{canalId}/membres", name="listeMembresCanal", methods={"GET"})
     */
    public function listeMembresCanal($canalId): CustomJsonResponse
    {
        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new CustomJsonResponse(null, 404, 'Canal non trouvé');
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

        return new CustomJsonResponse(['membres' => $membresData], 200, 'Liste des membres récupérée avec succès');
    }
}

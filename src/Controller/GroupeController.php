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
use App\Entity\Groupe;
use App\Entity\GroupeUser;
use App\Entity\TypeParticipant;
use App\Response\CustomJsonResponse;

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
    public function createGroupe(Request $request): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['libelle']) || !isset($data['description']) || !isset($data['userId'])) {
            return new CustomJsonResponse(null, 203, 'Données manquantes');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 203, 'Utilisateur non trouvé');
        }

        $groupe = new Groupe();
        $groupe->setLibelle($data['libelle']);
        $groupe->setDescription($data['description']);

        $groupeUser = new GroupeUser();
        $groupeUser->setGroupe($groupe);
        $groupeUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(1);
        if (!$typeUser) {
            return new CustomJsonResponse(null, 203, 'Type utilisateur non trouvé');
        }
        $groupeUser->setTypeParticipant($typeUser);

        $this->em->persist($groupe);
        $this->em->persist($groupeUser);
        $this->em->flush();

        return new CustomJsonResponse(['groupeId' => $groupe->getId()], 201, 'Groupe créé avec succès');
    }

    /**
     * @Route("/groupe/{groupeId}/set-admin", name="setGroupeAdmin", methods={"POST"})
     */
    public function setGroupeAdmin(Request $request, $groupeId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new CustomJsonResponse(null, 203, 'Données manquantes');
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new CustomJsonResponse(null, 203, 'Groupe non trouvé');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 203, 'Utilisateur non trouvé');
        }

        $groupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if (!$groupeUser) {
            return new CustomJsonResponse(null, 203, 'L\'utilisateur n\'est pas membre de ce groupe');
        }

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(2);
        if (!$typeUser) {
            return new CustomJsonResponse(null, 203, 'Type utilisateur non trouvé');
        }
        $groupeUser->setTypeParticipant($typeUser);

        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Administrateur défini avec succès');
    }

    /**
     * @Route("/groupe/{groupeId}/join", name="joinGroupe", methods={"POST"})
     */
    public function joinGroupe(Request $request, $groupeId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new CustomJsonResponse(null, 203, 'Données manquantes');
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new CustomJsonResponse(null, 203, 'Groupe non trouvé');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 203, 'Utilisateur non trouvé');
        }

        $existingGroupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if ($existingGroupeUser) {
            return new CustomJsonResponse(null, 203, 'L\'utilisateur est déjà membre de ce groupe');
        }

        $groupeUser = new GroupeUser();
        $groupeUser->setGroupe($groupe);
        $groupeUser->setMuntu($user);

        $typeUser = $this->em->getRepository(TypeParticipant::class)->find(3);
        if (!$typeUser) {
            return new CustomJsonResponse(null, 203, 'Type utilisateur non trouvé');
        }
        $groupeUser->setTypeParticipant($typeUser);

        $this->em->persist($groupeUser);
        $this->em->flush();

        return new CustomJsonResponse(null, 201, 'Utilisateur ajouté au groupe avec succès');
    }

    /**
     * @Route("/groupe/{groupeId}/leave", name="leaveGroupe", methods={"POST"})
     */
    public function leaveGroupe(Request $request, $groupeId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return new CustomJsonResponse(null, 203, 'Données manquantes');
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new CustomJsonResponse(null, 203, 'Groupe non trouvé');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 203, 'Utilisateur non trouvé');
        }

        $groupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if (!$groupeUser) {
            return new CustomJsonResponse(null, 203, 'L\'utilisateur n\'est pas membre de ce groupe');
        }

        $this->em->remove($groupeUser);
        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Utilisateur retiré du groupe avec succès');
    }

    /**
     * @Route("/groupe/{groupeId}/kick", name="kickFromGroupe", methods={"POST"})
     */
    public function kickFromGroupe(Request $request, $groupeId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId']) || !isset($data['userId'])) {
            return new CustomJsonResponse(null, 203, 'Données manquantes');
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new CustomJsonResponse(null, 203, 'Groupe non trouvé');
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new CustomJsonResponse(null, 203, 'Administrateur non trouvé');
        }

        $adminGroupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $admin,
            'typeUser' => 2
        ]);

        if (!$adminGroupeUser) {
            return new CustomJsonResponse(null, 403, 'Vous n\'avez pas les droits d\'administrateur pour ce groupe');
        }

        $user = $this->em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new CustomJsonResponse(null, 203, 'Utilisateur à expulser non trouvé');
        }

        $groupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $user
        ]);

        if (!$groupeUser) {
            return new CustomJsonResponse(null, 203, 'L\'utilisateur n\'est pas membre de ce groupe');
        }

        $this->em->remove($groupeUser);
        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Utilisateur expulsé du groupe avec succès');
    }

    /**
     * @Route("/groupe/{groupeId}/update", name="updateGroupe", methods={"PUT"})
     */
    public function updateGroupe(Request $request, $groupeId): CustomJsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adminId'])) {
            return new CustomJsonResponse(null, 203, 'Données manquantes');
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new CustomJsonResponse(null, 203, 'Groupe non trouvé');
        }

        $admin = $this->em->getRepository(User::class)->find($data['adminId']);
        if (!$admin) {
            return new CustomJsonResponse(null, 203, 'Administrateur non trouvé');
        }

        $adminGroupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'groupe' => $groupe,
            'muntu' => $admin,
            'typeUser' => 2
        ]);

        if (!$adminGroupeUser) {
            return new CustomJsonResponse(null, 403, 'Vous n\'avez pas les droits d\'administrateur pour ce groupe');
        }

        if (isset($data['libelle'])) {
            $groupe->setLibelle($data['libelle']);
        }

        if (isset($data['description'])) {
            $groupe->setDescription($data['description']);
        }

        $this->em->flush();

        return new CustomJsonResponse(null, 200, 'Informations du groupe mises à jour avec succès');
    }

    /**
     * @Route("/groupe/{groupeId}/membres", name="listeMembresGroupe", methods={"GET"})
     */
    public function listeMembresGroupe($groupeId): CustomJsonResponse
    {
        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new CustomJsonResponse(null, 203, 'Groupe non trouvé');
        }

        $membres = $this->em->getRepository(GroupeUser::class)->findBy(['groupe' => $groupe]);

        $membresData = [];
        foreach ($membres as $membre) {
            $membresData[] = [
                'id' => $membre->getMuntu()->getId(),
                'username' => $membre->getMuntu()->getNameUser(),
                'typeUser' => $membre->getTypeParticipant()->getLibelle()
            ];
        }

        return new CustomJsonResponse(['membres' => $membresData], 200, 'Liste des membres du groupe');
    }
}

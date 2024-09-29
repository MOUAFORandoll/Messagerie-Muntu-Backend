<?php

namespace App\Controller;

use App\Entity\TypeParticipant;
use App\Entity\User;
use App\FunctionU\TransactionFunction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\TypeUser;

class InitController extends AbstractController
{
    private $em;
    private $publicDirectory;
    private $transactionFunction;
    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        TransactionFunction $transactionFunction

    ) {
        $this->em = $em;
        $this->transactionFunction = $transactionFunction;
        $this->publicDirectory = $params->get('kernel.project_dir') . '/public';
    }

    #[Route('/babana_express/config', name: 'InitConfig', methods: ['GET'])]
    public function initConfig()
    {
        $directory = $this->createFileRepertory();
        $typeParticipant = $this->initTypeParticipant();
        $typeUser = $this->initTypeUser();

        return new JsonResponse([
            'type_user' => $typeUser,
            'type_participant' => $typeParticipant,

            'directory' => $directory,
        ], 200);
    }


    #[Route('/create-directory', name: 'create_directory', methods: ['GET'])]
    public function createFileRepertory()
    {
        $basePath = $this->publicDirectory;
        $subDirs = ['images', 'factures', 'images/message']; // Exemple de sous-dossiers

        foreach ($subDirs as $subDir) {
            $fullPath = $basePath . '/' . $subDir;

            if (!file_exists($fullPath)) {
                if (mkdir($fullPath, 0777, true)) {
                    echo "Dossier '$fullPath' créé avec succès.<br>";
                } else {
                    echo "Erreur lors de la création du dossier '$fullPath'.<br>";
                }
            } else {
                echo "Le dossier '$fullPath' existe déjà.<br>";
            }
        }

        return new JsonResponse([
            'message' => 'Success',
        ], 200);
    }


    #[Route('/babana_express/admin', name: 'EmiyAdminInit', methods: ['GET'])]
    public function emiyAdminInit()
    {
        $admin = $this->em->getRepository(User::class)->findOneBy(['id' => 1]);

        $typeUser = $this->em->getRepository(TypeUser::class)->findOneBy(['id' => 1]);
        $admin->setTypeUser($typeUser);

        $this->em->persist($admin);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Success',
        ], 200);
    }

    private function initTypeParticipant()
    {
        $types = ['Createur', 'Administrateur', 'membre'];
        $data = $this->em->getRepository(TypeParticipant::class)->findAll();

        if (count($data) >= count($types)) {
            return new JsonResponse(['message' => 'Exist'], 200);
        }

        foreach ($types as $typeName) {
            $type = new TypeParticipant();
            $type->setLibelle($typeName);

            $this->em->persist($type);
            $this->em->flush();
        }

        return new JsonResponse(['message' => 'Success'], 200);
    }

    private function initTypeUser()
    {
        $types = ['Admin', 'Membre',];
        $data = $this->em->getRepository(TypeUser::class)->findAll();

        if (count($data) >= count($types)) {
            return new JsonResponse(['message' => 'Exist'], 200);
        }

        foreach ($types as $typeName) {
            $type = new TypeUser();
            $type->setLibelle($typeName);

            $this->em->persist($type);
            $this->em->flush();
        }

        return new JsonResponse(['message' => 'Success'], 200);
    }
}
